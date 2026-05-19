<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Overdue kickback — {{ $job_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #222;
            line-height: 1.5;
        }

        .container {
            max-width: 560px;
            margin: 0 auto;
            padding: 24px;
        }

        .header {
            border-bottom: 2px solid #d33;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .meta {
            background: #f5f5f5;
            padding: 12px 16px;
            border-radius: 4px;
            margin: 16px 0;
        }

        .meta dt {
            font-weight: 600;
            display: inline-block;
            min-width: 130px;
        }

        .footer {
            color: #777;
            font-size: 13px;
            border-top: 1px solid #ddd;
            padding-top: 12px;
            margin-top: 24px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">Overdue Kickback</h2>
        </div>

        <p>Hi {{ $manager_name }},</p>

        <p>
            A job card kicked back to <strong>{{ $super_name }}</strong> hasn't been
            resubmitted within {{ $days_overdue }} days. This may need your attention.
        </p>

        <div class="meta">
            <dl style="margin: 0;">
                <dt>Job Number:</dt>
                <dd style="display:inline; margin:0;">{{ $job_number }}</dd><br>
                <dt>Card:</dt>
                <dd style="display:inline; margin:0;">{{ $card_link }}</dd><br>
                <dt>Super:</dt>
                <dd style="display:inline; margin:0;">{{ $super_name }} ({{ $super_email }})</dd><br>
                <dt>Kicked back:</dt>
                <dd style="display:inline; margin:0;">{{ $kicked_back_at }}</dd><br>
                <dt>Kickback count:</dt>
                <dd style="display:inline; margin:0;">{{ $kickback_count }}</dd>
            </dl>
        </div>

        <p>
            You can review the card and the AI's findings in PeekTrack.
            The super has been notified to fix and resubmit but hasn't done so yet.
        </p>

        <div class="footer">
            Sent by PeekTrack AI scoring (automated).
            You're receiving this because you're listed as {{ $super_name }}'s manager.
        </div>
    </div>
</body>

</html>
