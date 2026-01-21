<?php
namespace includes;

class OTPMailTemplate
{
    public static function getHtml($otp, $name): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
                .container { max-width: 500px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #004d40 0%, #00796b 100%); color: #ffffff; padding: 30px; text-align: center; }
                .header img { height: 160px; margin-bottom: 15px; padding: 8px; }
                .content { padding: 40px 30px; text-align: center; }
                .otp-box { background-color: #e0f2f1; color: #004d40; font-size: 32px; font-weight: bold; letter-spacing: 5px; padding: 15px; border-radius: 8px; margin: 25px 0; display: inline-block; border: 2px dashed #00796b; }
                .footer { background-color: #f8f9fa; color: #6c757d; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #eee; }
                .btn-verify { display: inline-block; background-color: #00796b; color: #ffffff; padding: 12px 25px; border-radius: 50px; text-decoration: none; font-weight: bold; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://www.cmpdi.co.in/sites/default/files/cmpdi_new_logo_10012025.png' alt='CMPDI Logo'>
                    <h2 style='margin:0; font-weight: 600; color: white;'>Email Verification</h2>
                </div>
                
                <div class='content'>
                    <p style='font-size: 16px; color: #333;'>Hello <strong>$name</strong>,</p>
                    <p style='color: #666; line-height: 1.6;'>Thank you for registering with the CMPDI Vehicle Booking System. To complete your account creation, please use the One-Time Password (OTP) below.</p>
                    
                    <div class='otp-box'>$otp</div>
                    
                    <p style='color: #666; font-size: 14px;'>This OTP is valid for <strong>10 minutes</strong>.<br>Do not share this code with anyone.</p>
                </div>

                <div class='footer'>
                    <p><strong>Central Mine Planning & Design Institute Regional Institute 4</strong><br>
                    Nagpur, Maharashtra 440014</p>
                    <p>&copy; " . date('Y') . " Vehicle Booking System</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
