<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>
</head>
<body style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; position: relative; -webkit-text-size-adjust: none; color: #718096; height: 100%; line-height: 1.4; margin: 0; padding: 0; width: 100% !important;">
<div style="padding: 25px;">
    <h1 style="color: #3d4852; font-size: 18px; font-weight: bold; margin-top: 0; text-align: left;">
        Welcome to {{ config('app.name') }}!
    </h1>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        Hi {{ $user->name }},
    </p>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        Thank you for signing up! Please verify your email address by clicking the button below.
    </p>

    <div style="text-align: center; margin: 25px 0;">
        <a href="{{ $url }}" style="background-color: #2d3748; border-radius: 4px; color: #fff; display: inline-block; padding: 8px 18px; text-decoration: none;">
            Verify Email Address
        </a>
    </div>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        After verifying, Basic plan will be enabled for you.
        You are limited to one shop and ten employees in Basic plan.
        After logging in, you can upgrade your account by clicking on the button below:
        <a href="https://app.lavorivo.com/subscription" style="background-color: #2d3748; border-radius: 4px; color: #fff; display: inline-block; padding: 8px 18px; text-decoration: none;">
            Subscription page.
        </a>
    </p>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        This verification link will expire in {{ $count }} minutes.
    </p>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        If you did not create an account, no further action is required.
    </p>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        Thanks,<br>
        {{ config('app.name') }}
    </p>
</div>
</body>
</html>
