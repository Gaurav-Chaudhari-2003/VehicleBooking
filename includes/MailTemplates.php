<?php
namespace includes;

class MailTemplates
{
    /* =====================================================
       USER MAIL TEMPLATE
    ===================================================== */
    public static function userMail(array $d): string
    {
        $statusColor = $d['status'] === 'APPROVED' ? '#28a745' : '#dc3545';
        $statusText = ucfirst(strtolower($d['status']));

        // Format Dates
        $pickupDate = date('d M Y, h:i A', strtotime($d['from']));
        $dropDate = date('d M Y', strtotime($d['to'])); // Date only for drop

        // Dynamic Map Link (Google Maps)
        $mapLink = "https://www.google.com/maps/dir/?api=1&origin=" . urlencode($d['pickup']) . "&destination=" . urlencode($d['drop']);

        // OpenStreetMap Static Map URL (using a free static map service like Geoapify or similar if available,
        // but for pure OpenStreetMap without API keys, we can't easily generate a route image.
        // However, we can use a generic map tile centered on the midpoint or just a nice map placeholder.
        // Since the user asked for "OpenStreetMap" specifically and "snapshot",
        // we will use a static map service that uses OSM data.
        // A popular free one is 'staticmap.openstreetmap.de' but it might be slow or rate limited.
        // Another option is constructing a URL to openstreetmap.org, but that's not an image.

        // Given the constraints (no API key provided), we will use a high-quality placeholder
        // that LOOKS like an OSM map, but clicking it goes to the dynamic map.
        // Or we can try to use a public static map generator if one exists without key.
        // Let's stick to the "Click to View Route" design but with an OSM-style background.

        $osmBackground = "https://staticmap.openstreetmap.de/staticmap.php?center=22.9734,78.6569&zoom=4&size=600x300&maptype=mapnik";
        // Note: The above service is a community service. If it fails, we fallback.
        // A safer bet for a "snapshot" without a key is a generic map background.

        // Vehicle Image
        $vehicleImgUrl = isset($d['vehicle_image']) && !empty($d['vehicle_image'])
            ? $d['base_url'] . 'vendor/img/' . $d['vehicle_image']
            : 'https://via.placeholder.com/300x150?text=Vehicle+Image';

        $callBtn = $d['driver_phone'] !== 'Not Assigned'
            ? "<a href='tel:{$d['driver_phone']}' style='background-color:#28a745; color:#ffffff; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block;'>📞 Call Driver</a>"
            : "<span style='color:#999; font-style:italic;'>No contact available</span>";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
                .header { background-color: #0056b3; color: #ffffff; padding: 20px; text-align: center; }
                .header img { height: 50px; margin-bottom: 10px; }
                .status-banner { background-color: $statusColor; color: #ffffff; text-align: center; padding: 10px; font-weight: bold; letter-spacing: 1px; }
                .content { padding: 30px; }
                .journey-card { background-color: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; border: 1px solid #e9ecef; }
                .vehicle-card { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
                .vehicle-img { width: 120px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 20px; }
                .driver-info { background-color: #eef2f7; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
                .footer { background-color: #343a40; color: #adb5bd; padding: 20px; text-align: center; font-size: 12px; }
                .btn-map { display: block; width: 100%; text-align: center; background-color: #007bff; color: #fff; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://www.cmpdi.co.in/sites/default/files/cmpdi_new_logo_10012025.png' alt='CMPDI Logo'>
                    <h2 style='margin:0;'>Vehicle Booking System</h2>
                </div>
                
                <div class='status-banner'>
                    BOOKING $statusText
                </div>

                <div class='content'>
                    <p>Hello <strong>{$d['user_name']}</strong>,</p>
                    <p>Your vehicle booking request has been processed. Here are the details of your upcoming journey.</p>

                    <!-- Journey Visualization -->
                    <div class='journey-card'>
                        <h3 style='margin-top:0; color:#444; border-bottom:1px solid #ddd; padding-bottom:10px;'>Journey Details</h3>
                        
                        <!-- Clickable Map Area -->
                        <a href='$mapLink' style='text-decoration:none; color:inherit; display:block;'>
                            <table width='100%' cellspacing='0' cellpadding='0' style='margin-bottom: 15px;'>
                                <tr>
                                    <td width='45%' valign='top'>
                                        <div style='color:#28a745; font-weight:bold; font-size:12px;'>PICKUP</div>
                                        <div style='font-size:16px; font-weight:bold; margin:5px 0;'>$pickupDate</div>
                                        <div style='color:#666; font-size:13px;'>{$d['pickup']}</div>
                                    </td>
                                    <td width='10%' align='center' valign='middle'>
                                        <div style='font-size:20px; color:#aaa;'>➝</div>
                                    </td>
                                    <td width='45%' valign='top' align='right'>
                                        <div style='color:#dc3545; font-weight:bold; font-size:12px;'>DROP</div>
                                        <div style='font-size:16px; font-weight:bold; margin:5px 0;'>$dropDate</div>
                                        <div style='color:#666; font-size:13px;'>{$d['drop']}</div>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Map Snapshot Placeholder (Clickable) -->
                            <!-- Using a generic OSM-style background pattern since we can't generate a real route image without a server-side renderer or paid API -->
                            <div style='background-color:#e9ecef; height:150px; border-radius:8px; display:flex; align-items:center; justify-content:center; background-image: url(\"https://upload.wikimedia.org/wikipedia/commons/thumb/b/b0/Openstreetmap_logo.svg/1200px-Openstreetmap_logo.svg.png\"); background-size:contain; background-repeat:no-repeat; background-position:center; border: 1px solid #ddd;'>
                                <div style='background:rgba(255,255,255,0.95); padding:10px 20px; border-radius:30px; font-weight:bold; color:#007bff; box-shadow:0 4px 10px rgba(0,0,0,0.15); display:flex; align-items:center;'>
                                    <span style='font-size:20px; margin-right:8px;'>🗺️</span> Click to View Route on Map
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Vehicle Details -->
                    <div class='vehicle-card'>
                        <img src='$vehicleImgUrl' alt='Vehicle' class='vehicle-img' onerror=\"this.onerror=null;this.src='https://via.placeholder.com/300x150?text=Vehicle+Image';\">
                        <div>
                            <h4 style='margin:0 0 5px 0; color:#333;'>{$d['vehicle']}</h4>
                            <div style='color:#666; font-size:14px;'>{$d['vehicle_reg_no']}</div>
                            <div style='color:#888; font-size:12px; margin-top:5px;'>Comfortable & Safe Journey</div>
                        </div>
                    </div>

                    <!-- Driver Details -->
                    <div class='driver-info'>
                        <div>
                            <div style='font-size:12px; color:#666; text-transform:uppercase;'>Your Driver</div>
                            <div style='font-size:16px; font-weight:bold; color:#333;'>{$d['driver']}</div>
                            <div style='font-size:14px; color:#555;'>{$d['driver_phone']}</div>
                        </div>
                        <div>
                            $callBtn
                        </div>
                    </div>

                    <!-- Admin Remark -->
                    <div style='margin-top:20px; background:#fff3cd; padding:15px; border-radius:8px; border-left:4px solid #ffc107;'>
                        <strong style='color:#856404;'>Admin Remark:</strong> <span style='color:#856404;'>{$d['remark']}</span>
                    </div>
                </div>

                <div class='footer'>
                    <p><strong>Central Mine Planning & Design Institute Regional Institute 4</strong><br>
                    Kasturba Nagar, Jaripatka, Nagpur, Maharashtra 440014</p>
                    <p><a href='https://www.cmpdi.co.in/' style='color:#adb5bd;'>www.cmpdi.co.in</a></p>
                    <p>&copy; " . date('Y') . " Vehicle Booking System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }


    /* =====================================================
       DRIVER MAIL TEMPLATE
    ===================================================== */
    public static function driverMail(array $d): string
    {
        $pickupDate = date('d M Y, h:i A', strtotime($d['from']));
        $dropDate = date('d M Y', strtotime($d['to']));

        $mapLink = "https://www.google.com/maps/dir/?api=1&origin=" . urlencode($d['pickup']) . "&destination=" . urlencode($d['drop']);

        $callUser =
            "<a href='tel:{$d['user_phone']}'
               style='background-color:#007bff; color:#ffffff; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block;'>
               📞 Call User
             </a>";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; }
                .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #343a40; color: #fff; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .trip-box { background: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
                .label { font-size: 12px; color: #666; font-weight: bold; text-transform: uppercase; }
                .value { font-size: 16px; color: #333; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin:0;'>New Trip Assignment</h2>
                </div>
                <div class='content'>
                    <p>Hello <strong>{$d['driver_name']}</strong>,</p>
                    <p>You have been assigned a new trip. Please review the details below.</p>

                    <div class='trip-box'>
                        <div class='label'>Passenger</div>
                        <div class='value'>{$d['user']}</div>
                        
                        <div class='label'>Vehicle</div>
                        <div class='value'>{$d['vehicle']}</div>
                        
                        <hr style='border:0; border-top:1px solid #ccc; margin:15px 0;'>
                        
                        <div class='label'>Pickup</div>
                        <div class='value'><strong>$pickupDate</strong><br>{$d['pickup']}</div>
                        
                        <div class='label'>Drop</div>
                        <div class='value'><strong>$dropDate</strong><br>{$d['drop']}</div>
                    </div>

                    <div style='text-align:center;'>
                        $callUser
                        <br><br>
                        <a href='$mapLink' style='color:#007bff; text-decoration:none;'>📍 View Route on Map</a>
                    </div>
                </div>
                <div style='background:#f8f9fa; padding:15px; text-align:center; font-size:12px; color:#666;'>
                    Drive Safely!
                </div>
            </div>
        </body>
        </html>";
    }


    /* =====================================================
       ICS CALENDAR GENERATOR
    ===================================================== */
    public static function ics(array $d): string
    {
        $uid = uniqid();

        $from = gmdate('Ymd\THis\Z', strtotime($d['from']));
        $to   = gmdate('Ymd\THis\Z', strtotime($d['to']));

        $pickup = addslashes($d['pickup']);
        $drop   = addslashes($d['drop']);

        return "BEGIN:VCALENDAR
            VERSION:2.0
            PRODID:-//Vehicle Booking//EN
            BEGIN:VEVENT
            UID:{$uid}
            DTSTART:{$from}
            DTEND:{$to}
            SUMMARY:Vehicle Trip - {$d['vehicle']}
            LOCATION:$pickup to {$drop}
            DESCRIPTION:Driver: {$d['driver']} | Phone: {$d['driver_phone']}
            END:VEVENT
            END:VCALENDAR";
    }
}
