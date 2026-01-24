<?php
session_start();

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    echo '<script>window.location.replace("../index.php");</script>';
    exit;
}

include('../DATABASE FILE/config.php');
include('../DATABASE FILE/checklogin.php');
check_login();
$aid = $_SESSION['a_id'] ?? null;

if (!$aid) { header("Location: ../index.php"); exit; }

// Fetch admin profile
global $mysqli;
$stmt = $mysqli->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $aid);
$stmt->execute();
$stmt->bind_result($fname, $lname);
$stmt->fetch();
$admin_name = $fname . ' ' . $lname;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Analytics | Vehicle Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Include Global Theme -->
    <?php include("../vendor/inc/theme-config.php"); ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 25px; margin-left: 260px; }

        /* Enhanced Stat Card */
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        .stat-card::after {
            content: ''; position: absolute; top: 0; right: 0; width: 60px; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4));
            transform: skewX(-20deg) translateX(150%); transition: 0.5s;
        }
        .stat-card:hover::after { transform: skewX(-20deg) translateX(-250%); }

        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .stat-value { font-size: 1.8rem; font-weight: 800; line-height: 1.1; color: #2c3e50; }
        .stat-label { font-size: 0.85rem; color: #7f8c8d; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }
        .stat-sub { font-size: 0.8rem; margin-top: 4px; font-weight: 500; }

        /* Tab Styling */
        .nav-pills { background: #fff; padding: 5px; border-radius: 50px; display: inline-flex; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .nav-pills .nav-link {
            color: #6c757d; font-weight: 600; padding: 8px 24px; border-radius: 50px; font-size: 0.9rem; transition: all 0.3s;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #00796b, #004d40);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 121, 107, 0.3);
        }

        .chart-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: none;
            height: 100%;
        }
        .chart-title { font-size: 1rem; font-weight: 700; color: #34495e; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

        .table-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            overflow: hidden;
            border: none;
        }

        .upcoming-item {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-left: 4px solid #00796b;
            transition: background 0.2s;
        }
        .upcoming-item:hover { background: #eef2f3; }

        /* Colors */
        .bg-soft-primary { background-color: #e3f2fd; color: #0d6efd; }
        .bg-soft-success { background-color: #d1e7dd; color: #198754; }
        .bg-soft-warning { background-color: #fff3cd; color: #ffc107; }
        .bg-soft-info { background-color: #cff4fc; color: #0dcaf0; }
        .bg-soft-danger { background-color: #f8d7da; color: #dc3545; }
        .bg-soft-purple { background-color: #e0cffc; color: #6f42c1; }

        /* Pulse Animation for Live Clock */
        .pulse-dot {
            width: 8px; height: 8px; background-color: #28a745; border-radius: 50%; display: inline-block; margin-right: 6px;
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(40, 167, 69, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }

        /* Quick Actions Dropdown */
        .quick-action-btn {
            background: #fff; border: 1px solid #e0e0e0; color: #333; font-weight: 600;
            padding: 8px 20px; border-radius: 50px; transition: all 0.3s;
        }
        .quick-action-btn:hover { background: #f8f9fa; border-color: #ccc; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include("vendor/inc/sidebar.php"); ?>

    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Dashboard</h3>
                <p class="text-muted mb-0 small">Welcome back, <strong><?php echo htmlspecialchars($admin_name); ?></strong></p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Quick Actions -->
                <div class="dropdown">
                    <button class="btn quick-action-btn dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bolt text-warning me-1"></i> Quick Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
                        <li><a class="dropdown-item py-2" href="admin-add-user.php"><i class="fas fa-user-plus me-2 text-primary"></i> Add User</a></li>
                        <li><a class="dropdown-item py-2" href="admin-add-vehicle.php"><i class="fas fa-bus me-2 text-success"></i> Add Vehicle</a></li>
                    </ul>
                </div>

                <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm d-flex align-items-center">
                    <span class="pulse-dot"></span> <span id="live-clock" class="font-monospace"></span>
                </span>
            </div>
        </div>

        <!-- Compact Metrics Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" onclick="window.location.href='admin-dashboard.php'">
                    <div>
                        <div class="stat-value" id="total-bookings">0</div>
                        <div class="stat-label">Bookings</div>
                        <div class="stat-sub text-success"><i class="fas fa-arrow-up"></i> <span id="bookings-today">0</span> Today</div>
                    </div>
                    <div class="stat-icon bg-soft-primary"><i class="fas fa-calendar-check"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" onclick="window.location.href='admin-dashboard.php'"> <!-- Ideally link to filtered pending list -->
                    <div>
                        <div class="stat-value" id="pending-bookings">0</div>
                        <div class="stat-label">Pending</div>
                        <div class="stat-sub text-warning">Action Req.</div>
                    </div>
                    <div class="stat-icon bg-soft-warning"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" onclick="window.location.href='admin-view-vehicle.php'">
                    <div>
                        <div class="stat-value" id="active-vehicles">0</div>
                        <div class="stat-label">Active Fleet</div>
                        <div class="stat-sub text-muted">of <span id="total-vehicles">0</span> Total</div>
                    </div>
                    <div class="stat-icon bg-soft-success"><i class="fas fa-bus"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" onclick="window.location.href='admin-view-vehicle.php'">
                    <div>
                        <div class="stat-value" id="maintenance-vehicles">0</div>
                        <div class="stat-label">Maintenance</div>
                        <div class="stat-sub text-danger">Unavailable</div>
                    </div>
                    <div class="stat-icon bg-soft-danger"><i class="fas fa-tools"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" onclick="window.location.href='admin-view-user.php'">
                    <div>
                        <div class="stat-value" id="total-users">0</div>
                        <div class="stat-label">Users</div>
                        <div class="stat-sub text-info"><span id="total-drivers">0</span> Drivers</div>
                    </div>
                    <div class="stat-icon bg-soft-info"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" onclick="window.location.href='admin-view-vehicle.php'">
                    <div>
                        <div class="stat-value" id="vendor-vehicles">0</div>
                        <div class="stat-label">Vendor</div>
                        <div class="stat-sub text-muted">Vehicles</div>
                    </div>
                    <div class="stat-icon bg-soft-purple"><i class="fas fa-handshake"></i></div>
                </div>
            </div>
        </div>

        <!-- Tabbed Analytics -->
        <div class="d-flex justify-content-center mb-4">
            <ul class="nav nav-pills" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pills-overview-tab" data-bs-toggle="pill" data-bs-target="#pills-overview" type="button" role="tab">Overview</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-fleet-tab" data-bs-toggle="pill" data-bs-target="#pills-fleet" type="button" role="tab">Fleet Analytics</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-performance-tab" data-bs-toggle="pill" data-bs-target="#pills-performance" type="button" role="tab">Top Performers</button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="pills-tabContent">

            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="pills-overview" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="chart-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="chart-title mb-0"><i class="fas fa-chart-line text-primary"></i> Booking Trends (7 Days)</div>
                                <div class="chart-title mb-0"><i class="fas fa-chart-pie text-info"></i> Status</div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 border-end">
                                    <canvas id="trendChart" height="220"></canvas>
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <div style="height: 200px; width: 100%; position: relative;">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="chart-card p-0 d-flex flex-column">
                            <div class="p-3 border-bottom bg-light rounded-top-3">
                                <div class="chart-title mb-0"><i class="fas fa-calendar-alt text-warning"></i> Upcoming Trips (48h)</div>
                            </div>
                            <div id="upcoming-trips-list" class="flex-grow-1 p-3" style="height: 230px; overflow-y: auto;">
                                <!-- Populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings Table -->
                <div class="table-card mt-4">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                        <span class="fw-bold text-dark"><i class="fas fa-list me-2 text-secondary"></i> Recent Activity</span>
                        <div class="d-flex gap-2">
                            <input type="text" id="tableSearch" class="form-control form-control-sm rounded-pill" placeholder="Search..." style="width: 200px;">
                            <a href="admin-dashboard.php" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold">View All</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" style="font-size: 0.9rem;">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4 py-3">ID</th>
                                    <th class="py-3">User</th>
                                    <th class="py-3">Vehicle</th>
                                    <th class="py-3">Driver</th>
                                    <th class="py-3">Journey Details</th>
                                    <th class="py-3">Status</th>
                                    <th class="text-end pe-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody id="recent-bookings-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Fleet Analytics Tab -->
            <div class="tab-pane fade" id="pills-fleet" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="chart-card">
                            <div class="chart-title"><i class="fas fa-car text-primary"></i> Vehicle Categories</div>
                            <div style="height: 250px; position: relative;">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-card">
                            <div class="chart-title"><i class="fas fa-gas-pump text-danger"></i> Fuel Type Distribution</div>
                            <div style="height: 250px; position: relative;">
                                <canvas id="fuelChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-card">
                            <div class="chart-title"><i class="fas fa-building text-success"></i> Fleet Ownership</div>
                            <div style="height: 250px; position: relative;">
                                <canvas id="ownershipChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Tab -->
            <div class="tab-pane fade" id="pills-performance" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="chart-card">
                            <div class="chart-title"><i class="fas fa-trophy text-warning"></i> Top 5 Vehicles</div>
                            <div style="height: 250px; position: relative;">
                                <canvas id="topVehiclesChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-card">
                            <div class="chart-title"><i class="fas fa-id-card text-info"></i> Top 5 Drivers</div>
                            <div style="height: 250px; position: relative;">
                                <canvas id="topDriversChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-card">
                            <div class="chart-title"><i class="fas fa-user-tag text-purple"></i> Top 5 Requesters</div>
                            <div style="height: 250px; position: relative;">
                                <canvas id="topUsersChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    setInterval(() => {
        document.getElementById('live-clock').innerText = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }, 1000);

    let trendChart, statusChart, categoryChart, fuelChart, topVehiclesChart, topDriversChart, topUsersChart, ownershipChart;

    function initCharts() {
        // Common Options
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15, font: { size: 11 } } },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: true,
                    usePointStyle: true
                }
            }
        };

        // Trend Chart
        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        const gradientTrend = ctxTrend.createLinearGradient(0, 0, 0, 400);
        gradientTrend.addColorStop(0, 'rgba(0, 121, 107, 0.2)');
        gradientTrend.addColorStop(1, 'rgba(0, 121, 107, 0)');

        trendChart = new Chart(ctxTrend, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Bookings', data: [], borderColor: '#00796b', backgroundColor: gradientTrend, borderWidth: 2, tension: 0.4, fill: true, pointRadius: 3, pointHoverRadius: 6 }] },
            options: {
                ...commonOptions,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f0f0f0' } }, x: { grid: { display: false } } }
            }
        });

        // Status Chart
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        statusChart = new Chart(ctxStatus, {
            type: 'doughnut',
            data: { labels: ['Approved', 'Pending', 'Rejected', 'Cancelled'], datasets: [{ data: [0, 0, 0, 0], backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d'], borderWidth: 0, hoverOffset: 5 }] },
            options: { ...commonOptions, cutout: '75%' }
        });

        // Category Chart
        const ctxCat = document.getElementById('categoryChart').getContext('2d');
        categoryChart = new Chart(ctxCat, {
            type: 'pie',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'], borderWidth: 0, hoverOffset: 5 }] },
            options: commonOptions
        });

        // Fuel Chart
        const ctxFuel = document.getElementById('fuelChart').getContext('2d');
        fuelChart = new Chart(ctxFuel, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#fd7e14', '#20c997', '#6610f2', '#6f42c1'], borderWidth: 0, hoverOffset: 5 }] },
            options: { ...commonOptions, cutout: '65%' }
        });

        // Ownership Chart
        const ctxOwn = document.getElementById('ownershipChart').getContext('2d');
        ownershipChart = new Chart(ctxOwn, {
            type: 'pie',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#198754', '#ffc107'], borderWidth: 0, hoverOffset: 5 }] },
            options: commonOptions
        });

        // Bar Charts Config
        const barOptions = {
            ...commonOptions,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
        };

        const ctxVeh = document.getElementById('topVehiclesChart').getContext('2d');
        topVehiclesChart = new Chart(ctxVeh, { type: 'bar', data: { labels: [], datasets: [{ label: 'Bookings', data: [], backgroundColor: '#00796b', borderRadius: 6, barThickness: 20 }] }, options: barOptions });

        const ctxDriver = document.getElementById('topDriversChart').getContext('2d');
        topDriversChart = new Chart(ctxDriver, { type: 'bar', data: { labels: [], datasets: [{ label: 'Trips', data: [], backgroundColor: '#0d6efd', borderRadius: 6, barThickness: 20 }] }, options: barOptions });

        const ctxUser = document.getElementById('topUsersChart').getContext('2d');
        topUsersChart = new Chart(ctxUser, { type: 'bar', data: { labels: [], datasets: [{ label: 'Bookings', data: [], backgroundColor: '#6610f2', borderRadius: 6, barThickness: 20 }] }, options: barOptions });
    }

    function updateDashboard() {
        $.ajax({
            url: 'fetch-dashboard-data.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                // Update Counts
                $('#total-bookings').text(data.counts.bookings);
                $('#pending-bookings').text(data.counts.pending);
                $('#bookings-today').text(data.counts.bookings_today);
                $('#active-vehicles').text(data.counts.active_vehicles);
                $('#total-vehicles').text(data.counts.vehicles);
                $('#maintenance-vehicles').text(data.counts.maintenance);
                $('#total-users').text(parseInt(data.counts.employees) + parseInt(data.counts.drivers));
                $('#total-drivers').text(data.counts.drivers);
                $('#vendor-vehicles').text(data.counts.vendor_vehicles);

                // Update Charts
                trendChart.data.labels = data.trends.labels;
                trendChart.data.datasets[0].data = data.trends.data;
                trendChart.update();

                const s = data.booking_stats;
                statusChart.data.datasets[0].data = [s.APPROVED||0, s.PENDING||0, s.REJECTED||0, s.CANCELLED||0];
                statusChart.update();

                categoryChart.data.labels = data.vehicle_categories.labels;
                categoryChart.data.datasets[0].data = data.vehicle_categories.data;
                categoryChart.update();

                fuelChart.data.labels = data.vehicle_fuel.labels;
                fuelChart.data.datasets[0].data = data.vehicle_fuel.data;
                fuelChart.update();

                ownershipChart.data.labels = data.fleet_ownership.labels;
                ownershipChart.data.datasets[0].data = data.fleet_ownership.data;
                ownershipChart.update();

                topVehiclesChart.data.labels = data.top_vehicles.labels;
                topVehiclesChart.data.datasets[0].data = data.top_vehicles.data;
                topVehiclesChart.update();

                topDriversChart.data.labels = data.top_drivers.labels;
                topDriversChart.data.datasets[0].data = data.top_drivers.data;
                topDriversChart.update();

                topUsersChart.data.labels = data.top_users.labels;
                topUsersChart.data.datasets[0].data = data.top_users.data;
                topUsersChart.update();

                // Update Upcoming Trips
                const tripList = $('#upcoming-trips-list');
                tripList.empty();
                if(data.upcoming_trips.length === 0) {
                    tripList.append('<div class="text-center text-muted py-5 small">No upcoming trips</div>');
                } else {
                    data.upcoming_trips.forEach(trip => {
                        const time = new Date(trip.from_datetime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        const date = new Date(trip.from_datetime).toLocaleDateString([], {day: 'numeric', month:'short'});
                        tripList.append(`
                            <div class="upcoming-item">
                                <div>
                                    <div class="fw-bold text-dark small">${trip.v_name}</div>
                                    <div class="small text-muted" style="font-size:0.75rem"><i class="fas fa-user me-1"></i> ${trip.first_name} ${trip.last_name}</div>
                                </div>
                                <span class="badge bg-white text-dark border shadow-sm">${date}, ${time}</span>
                            </div>
                        `);
                    });
                }

                // Update Table
                const tbody = $('#recent-bookings-body');
                tbody.empty();
                if (data.bookings.length === 0) {
                    tbody.append('<tr><td colspan="6" class="text-center py-3 text-muted small">No recent activity</td></tr>');
                } else {
                    data.bookings.forEach(row => {
                        const statusColors = {'PENDING': 'bg-warning text-dark', 'APPROVED': 'bg-success', 'REJECTED': 'bg-danger', 'CANCELLED': 'bg-secondary'};
                        const badge = statusColors[row.status] || 'bg-secondary';
                        
                        // Format dates and times
                        const fromDateObj = new Date(row.from_datetime);
                        const toDateObj = new Date(row.to_datetime);
                        
                        const fromDate = fromDateObj.toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'2-digit'});
                        const fromTime = fromDateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        const toDate = toDateObj.toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'2-digit'});
                        const toTime = toDateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        let driverHtml = '<span class="text-muted small">Not Assigned</span>';
                        if (row.d_fname) {
                            driverHtml = `
                                <div class="fw-bold small">${row.d_fname} ${row.d_lname}</div>
                                <div class="text-muted" style="font-size:0.75rem">${row.d_phone}</div>
                            `;
                        }

                        const html = `
                            <tr>
                                <td class="ps-4 fw-bold text-muted small">${row.booking_id}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="fw-bold small">${row.u_fname} ${row.u_lname}</div>
                                            <div class="text-muted" style="font-size:0.75rem">${row.u_email} | ${row.u_phone}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold small">${row.v_name}</div>
                                    <div class="text-muted" style="font-size:0.75rem">${row.v_reg_no}</div>
                                </td>
                                <td>${driverHtml}</td>
                                <td>
                                    <small class="fw-semibold text-muted d-block">From: ${fromDate} | ${fromTime}</small>
                                    <small class="fw-semibold text-muted d-block">To: ${toDate} | ${toTime}</small>
                                </td>
                                <td><span class="badge ${badge} text-white" style="font-size:0.7rem">${row.status}</span></td>
                                <td class="text-end pe-4">
                                    <a href="admin-approve-booking.php?booking_id=${row.booking_id}" class="btn btn-sm btn-light border hover-shadow py-0 px-2">
                                        <i class="fas fa-arrow-right text-primary" style="font-size:0.8rem"></i>
                                    </a>
                                </td>
                            </tr>
                        `;
                        tbody.append(html);
                    });
                }
            }
        });
    }

    // Search Functionality
    $('#tableSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#recent-bookings-body tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $(document).ready(function() {
        initCharts();
        updateDashboard();
        setInterval(updateDashboard, 30000);
    });
</script>

</body>
</html>
