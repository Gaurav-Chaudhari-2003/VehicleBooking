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

        $callBtn = $d['driver_phone'] !== 'Not Assigned'
            ? "<a href='tel:{$d['driver_phone']}'
                 style='background:#007bff;color:#fff;
                        padding:8px 14px;border-radius:6px;
                        text-decoration:none'>
                 📞 Call Driver
               </a>"
            : "";

        $maps = urlencode($d['pickup'] . " to " . $d['drop']);

        return "
        <div style='font-family:Segoe UI,Arial,serif;padding:20px'>
            <h2 style='color:$statusColor'>
                Booking {$d['status']}
            </h2>

            <table width='100%' cellpadding='6'>
            <tr><td><b>Vehicle</b></td><td>{$d['vehicle']}</td></tr>
            <tr><td><b>From</b></td><td>{$d['from']}</td></tr>
            <tr><td><b>To</b></td><td>{$d['to']}</td></tr>
            <tr><td><b>Pickup</b></td><td>{$d['pickup']}</td></tr>
            <tr><td><b>Drop</b></td><td>{$d['drop']}</td></tr>
            <tr><td><b>Driver</b></td><td>{$d['driver']}</td></tr>
            <tr><td><b>Phone</b></td><td>{$d['driver_phone']}</td></tr>
            </table>

            <p>
            <a href='https://www.google.com/maps/search/?api=1&query=$maps'
               style='background:#28a745;color:#fff;
                      padding:8px 14px;border-radius:6px;
                      text-decoration:none'>
               📍 Open in Maps
            </a>
            {$callBtn}
            </p>

            <p style='background:#f8f9fa;padding:10px'>
                <b>Admin Remark:</b> {$d['remark']}
            </p>

            <p style='font-size:12px;color:#666'>
                Vehicle Booking System
            </p>
        </div>";
    }


    /* =====================================================
       DRIVER MAIL TEMPLATE
    ===================================================== */
    public static function driverMail(array $d): string
    {
        $callUser =
            "<a href='tel:{$d['user_phone']}'
               style='background:#007bff;color:#fff;
                      padding:8px 14px;border-radius:6px;
                      text-decoration:none'>
               📞 Call User
             </a>";

        return "
        <div style='font-family:Segoe UI,Arial,serif;padding:20px'>

        <h2 style='color:#007bff'>
            You Are Assigned as Driver
        </h2>

        <table width='100%' cellpadding='6'>
        <tr><td><b>User</b></td><td>{$d['user']}</td></tr>
        <tr><td><b>Vehicle</b></td><td>{$d['vehicle']}</td></tr>
        <tr><td><b>From</b></td><td>{$d['from']}</td></tr>
        <tr><td><b>To</b></td><td>{$d['to']}</td></tr>
        <tr><td><b>Pickup</b></td><td>{$d['pickup']}</td></tr>
        <tr><td><b>Drop</b></td><td>{$d['drop']}</td></tr>
        </table>

        <p>$callUser</p>

        </div>";
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
