<?php
// Theme Configuration File
// Include this file in the <head> section of your pages to apply the global theme styles.
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    :root {
        --primary-color: #004d40;
        --secondary-color: #00796b;
        --accent-color: #e0f2f1;
        --text-color: #333;
        --light-bg: #f8f9fa;
    }

    body {
        font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
        background-color: var(--light-bg);
        color: var(--text-color);
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* Top Bar for Admin/Secondary Actions */
    .top-bar {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        padding: 20px;
        display: flex;
        justify-content: flex-end;
        z-index: 1000;
    }

    /* Hero Section */
    .hero-section {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 100px 20px 60px;
        background: linear-gradient(135deg, var(--accent-color) 0%, #ffffff 100%);
    }

    .hero-content {
        max-width: 800px;
        animation: fadeIn 1s ease-out;
    }

    .hero-logo {
        max-width: 250px;
        margin-bottom: 25px;
    }

    .hero-title {
        font-size: 3rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
    }

    .hero-subtitle {
        font-size: 1.3rem;
        color: #555;
        margin-bottom: 3rem;
        font-weight: 300;
    }

    .btn-hero {
        padding: 14px 40px;
        font-size: 1.1rem;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        min-width: 160px;
    }

    .btn-hero:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }
    
    .btn-admin {
        font-size: 0.9rem;
        padding: 8px 20px;
        border-radius: 30px;
        border: 1px solid #ccc;
        color: #666;
        background: rgba(255,255,255,0.8);
        transition: all 0.2s;
    }
    
    .btn-admin:hover {
        background: #fff;
        color: var(--primary-color);
        border-color: var(--primary-color);
        text-decoration: none;
    }

    /* Features Section */
    .features-section {
        padding: 80px 0;
        background-color: #fff;
        border-top: 1px solid #eee;
    }

    .feature-box {
        padding: 20px;
        text-align: center;
    }

    .feature-icon {
        font-size: 2rem;
        color: var(--secondary-color);
        margin-bottom: 15px;
        background: var(--accent-color);
        width: 70px;
        height: 70px;
        line-height: 70px;
        border-radius: 50%;
    }

    .feature-title {
        font-weight: 700;
        margin-bottom: 10px;
        color: var(--primary-color);
    }
    
    .feature-text {
        font-size: 0.95rem;
        color: #666;
        line-height: 1.6;
    }

    /* Footer */
    .footer {
        background-color: var(--primary-color);
        color: rgba(255,255,255,0.7);
        padding: 25px 0;
        text-align: center;
        font-size: 0.85rem;
        margin-top: auto;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
