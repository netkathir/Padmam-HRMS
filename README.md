# Padmam-HRMS

## ⚠️ BEFORE GOING LIVE — pending discussion: cron setup

The yearly leave balance reset (`leave:reset-yearly-balances`, scheduled to run every **January 1st at 00:00**) depends on a server cron job calling Laravel's scheduler every minute:

```
* * * * * cd /home/<user>/public_html/padhmam_hrms && php artisan schedule:run >> /dev/null 2>&1
```

**This has not been finalized yet** — there's an open concern to discuss about how the cron is set up in cPanel before relying on it in production. Do not treat the yearly reset as live/working until this is confirmed and the cron job is actually added on the server.
