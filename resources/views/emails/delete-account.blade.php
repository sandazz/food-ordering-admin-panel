<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Account Deletion</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f5f5f5;">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; text-align: center; border-radius: 12px 12px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">⚠️ Account Deletion Request</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 20px;">
                                Hello,
                            </p>
                            
                            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 20px;">
                                We received a request to delete the Moi ! Poke account associated with <strong>{{ $email }}</strong>.
                            </p>
                            
                            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 25px 0; border-radius: 4px;">
                                <p style="font-size: 15px; color: #856404; margin: 0; line-height: 1.6;">
                                    <strong>Important:</strong> This action is permanent and cannot be undone. All your data, including order history and personal information, will be permanently deleted.
                                </p>
                            </div>
                            
                            <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 30px;">
                                If you requested this deletion, please click the button below to confirm:
                            </p>
                            
                            <!-- Button -->
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{{ $verificationUrl }}" style="display: inline-block; padding: 16px 40px; background-color: #e74c3c; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                            Confirm Account Deletion
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="font-size: 14px; color: #666; line-height: 1.6; margin-top: 30px; margin-bottom: 20px;">
                                Or copy and paste this link into your browser:
                            </p>
                            
                            <p style="font-size: 13px; color: #667eea; word-break: break-all; background-color: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #dee2e6;">
                                {{ $verificationUrl }}
                            </p>
                            
                            <div style="background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin: 30px 0; border-radius: 4px;">
                                <p style="font-size: 14px; color: #1565c0; margin: 0; line-height: 1.6;">
                                    <strong>Didn't request this?</strong><br>
                                    If you did not request account deletion, please ignore this email. Your account will remain active and no changes will be made. This link will expire in 1 hour.
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-radius: 0 0 12px 12px; border-top: 1px solid #dee2e6;">
                            <p style="font-size: 14px; color: #6c757d; margin: 0 0 10px 0;">
                                Moi ! Poke
                            </p>
                            <p style="font-size: 12px; color: #6c757d; margin: 0;">
                                This is an automated message, please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
