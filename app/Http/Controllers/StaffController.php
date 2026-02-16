<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BootstrapTableService;
use App\Services\HelperService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Throwable;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['staff-list', 'staff-create', 'staff-edit', 'staff-delete']);
        $roles = Role::where('custom_role', 1)->get();
        return view('staff.index', compact('roles'), ['type_menu' => 'staffs']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        ResponseService::noPermissionThenRedirect(['staff-create']);
        $roles = Role::where('custom_role', 1)->get();
        return view('staff.create', compact('roles'), ['type_menu' => 'staffs']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        ResponseService::noPermissionThenRedirect(['staff-create']);
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $slug = HelperService::generateUniqueSlug(User::class, $request->name);
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(explode('@', $request->email)[0]),
                'slug' => $slug,
                'is_active' => $request->has('is_active') ? 1 : 0,
            ]);
            $user->syncRoles([$request->role, config('constants.SYSTEM_ROLES.STAFF')]);
            DB::commit();
            ResponseService::successResponse('Staff Created Successfully', null, ['redirect_url' => route(
                'staffs.index',
            )]);
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorRedirect($e, 'StaffController --> store');
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        ResponseService::noPermissionThenRedirect('staff-list');
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';
        $showDeleted = $request->show_deleted ?? 0;

        // Only include soft-deleted records if show_deleted is explicitly set to 1
        if ($showDeleted == 1 || $showDeleted === '1') {
            $sql = User::withTrashed()
                ->with('roles')
                ->orderBy($sort, $order)
                ->whereHas('roles', static function ($q): void {
                    $q->where('custom_role', 1);
                })
                ->onlyTrashed();
        } else {
            // Default: exclude soft-deleted records
            $sql = User::with('roles')
                ->orderBy($sort, $order)
                ->whereHas('roles', static function ($q): void {
                    $q->where('custom_role', 1);
                })
                ->whereNull('deleted_at');
        }

        if (!empty($request->search)) {
            $sql->where(static function ($q) use ($request): void {
                $q->where('name', 'like', '%' . $request->search . '%')->orWhere(
                    'email',
                    'like',
                    '%' . $request->search . '%',
                );
            });
        }

        $total = $sql->count();
        $sql->skip($offset)->take($limit);
        $result = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        foreach ($result as $row) {
            $operate = '';
            $operate .= BootstrapTableService::editButton(route('staffs.update', $row->id), true);
            $operate .= BootstrapTableService::editButton(
                route('staffs.change-password', $row->id),
                true,
                '#resetPasswordModel',
                null,
                $row->id,
                'fas fa-key',
            );
            $operate .= BootstrapTableService::deleteButton(route('staffs.destroy', $row->id));

            $tempRow = $row->toArray();
            $tempRow['status'] = empty($row->deleted_at);
            $tempRow['operate'] = $operate;
            // Get the custom role (excluding STAFF role) for edit form
            $customRole = $row->roles->where('custom_role', 1)->first();
            $tempRow['role_id'] = $customRole ? $customRole->id : null;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        ResponseService::noPermissionThenRedirect('staff-edit');
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'role_id' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $user = User::withTrashed()->findOrFail($id);
            $data = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            if ($request->email !== $user->email) {
                $data['password'] = Hash::make(explode('@', $request->email)[0]);
            }

            $user->update($data);

            $oldRole = $user->roles;
            if ($oldRole[0]->id !== $request->role_id) {
                $newRole = Role::findById($request->role_id);
                $user->removeRole($oldRole[0]);
                $user->assignRole($newRole);
            }

            DB::commit();
            ResponseService::successResponse('User Update Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorRedirect($th, 'StaffController --> update');
            ResponseService::errorResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            ResponseService::noPermissionThenSendJson('staff-delete');
            $user = User::findOrFail($id);
            $user->delete();
            ResponseService::successResponse('Staff Deleted Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th, 'StaffController --> delete');
            ResponseService::errorResponse('Failed to delete staff');
        }
    }

    public function changePassword(Request $request, $id)
    {
        ResponseService::noPermissionThenRedirect('staff-edit');
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            User::findOrFail($id)->update(['password' => Hash::make($request->confirm_password)]);
            ResponseService::successResponse('Password Reset Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th, 'StaffController -> changePassword');
            ResponseService::errorResponse();
        }
    }
}
