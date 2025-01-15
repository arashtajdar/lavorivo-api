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
        Hi,
    </p>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        Your account has been created. Here are your login credentials:
    </p>

    <div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0; font-size: 16px;">
            <strong>Email:</strong> {{ $user->email }}<br>
            <strong>Password:</strong> {{ $password }}
        </p>
    </div>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        Please verify your email address by clicking the button below.
    </p>

    <div style="text-align: center; margin: 25px 0;">
        <a href="{{ $verificationUrl }}" style="background-color: #2d3748; border-radius: 4px; color: #fff; display: inline-block; padding: 8px 18px; text-decoration: none;">
            Verify Email Address
        </a>
    </div>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        For security reasons, please change your password after your first login.
    </p>

    <p style="font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">
        Thanks,<br>
        {{ config('app.name') }}
    </p>
</div>
</body>
</html>
