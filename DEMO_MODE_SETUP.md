# Demo Mode Setup Guide

## Overview
Demo mode automatically cleans up test data while protecting your demo data. When `DEMO_MODE=1` in `.env`:
- **Demo data** (created before 12/11/2025) is **protected** from editing/deletion
- **Test data** (created after 12/11/2025) is **automatically deleted** after 8 hours

## Setup Instructions

### 1. Enable Demo Mode
Add to your `.env` file:
```env
DEMO_MODE=1
```

### 2. Configure Scheduler
Make sure Laravel scheduler is running. Add to your crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or for Windows (using Task Scheduler):
- Create a task that runs: `php artisan schedule:run`
- Set it to run every minute

### 3. Manual Cleanup (Optional)
You can manually run the cleanup command:
```bash
php artisan demo:cleanup
```

## How It Works

### Protected Demo Data
- **Cutoff Date**: 12/11/2025 00:00:00
- Any entry created **before** this date is considered demo data
- Demo data **cannot be edited or deleted** when demo mode is enabled

### Auto-Cleanup
- Entries created **after** 12/11/2025
- Older than **8 hours**
- Automatically deleted by scheduled task (runs every hour)

### Protected Models
The following models have demo data protection:
- `Order`
- `Course`
- `User`

## Testing

### Test Demo Mode
1. Set `DEMO_MODE=1` in `.env`
2. Try to edit/delete an entry created before 12/11/2025
3. It should be blocked and logged

### Test Auto-Cleanup
1. Create a test entry (after 12/11/2025)
2. Wait 8+ hours OR manually run: `php artisan demo:cleanup`
3. The entry should be deleted

## Important Notes

⚠️ **Warning**: 
- Demo mode protects data created **before** 12/11/2025
- All data created **after** 12/11/2025 will be deleted after 8 hours
- Make sure your demo data is created before the cutoff date

## Troubleshooting

### Cleanup not running?
1. Check if scheduler is running: `php artisan schedule:list`
2. Check logs: `storage/logs/laravel.log`
3. Manually test: `php artisan demo:cleanup`

### Demo data being deleted?
- Check the `created_at` date - it must be before 12/11/2025
- Check if `DEMO_MODE=1` is set correctly

### Can't edit demo data?
- This is expected behavior when `DEMO_MODE=1`
- Check logs for protection messages
- Set `DEMO_MODE=0` to disable protection (not recommended for demo sites)

