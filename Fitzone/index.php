<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'config.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitZone Fitness Center - Kurunegala</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Base Styles */
        :root {
            --primary-color: #00b4d8;
            --primary-dark: #0096b4;
            --secondary-color: #ff6b6b;
            --dark-color: #1a1a1a;
            --light-color: #f5f5f5;
            --gray-color: #e0e0e0;
            --text-color: #333;
            --white: #ffffff;
            --box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--light-color);
            overflow-x: hidden;
        }

        h1, h2, h3, h4 {
            font-weight: 600;
            line-height: 1.2;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section {
            padding: 6rem 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: var(--dark-color);
            position: relative;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            margin: 1rem auto;
            border-radius: 2px;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: var(--transition);
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 10px rgba(0, 180, 216, 0.3);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 180, 216, 0.4);
        }

        .btn-outline {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: var(--white);
            transform: translateY(-3px);
        }

        .text-center {
            text-align: center;
        }

        /* Navigation */
        .navbar {
            background: rgba(26, 26, 26, 0.95);
            padding: 1.2rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .navbar.scrolled {
            padding: 0.8rem 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .logo {
            color: var(--white);
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .logo span {
            color: var(--primary-color);
            margin-left: 0.3rem;
        }

        .logo i {
            margin-right: 0.5rem;
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
        }

        .nav-links a {
            color: var(--white);
            font-weight: 500;
            font-size: 1rem;
            position: relative;
            padding: 0.5rem 0;
            transition: var(--transition);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: var(--transition);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .hamburger {
            display: none;
            cursor: pointer;
            color: var(--white);
            font-size: 1.5rem;
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('images/gym-hero.jpg') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--white);
            padding-top: 80px;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: linear-gradient(to top, var(--light-color), transparent);
            z-index: 1;
        }

        .hero-content {
            max-width: 800px;
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.5);
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
        }

        .hero .btn {
            padding: 1rem 2.5rem;
            font-size: 1rem;
        }

        /* Features Section */
        .features {
            background-color: var(--white);
            position: relative;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
        }

        .feature-card {
            padding: 2.5rem 2rem;
            background: var(--white);
            border-radius: 10px;
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
            border-bottom: 4px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-bottom-color: var(--primary-color);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }

        /* Popular Classes */
        .classes {
            background-color: var(--light-color);
        }

        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .class-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .class-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .class-img {
            height: 220px;
            overflow: hidden;
        }

        .class-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .class-card:hover img {
            transform: scale(1.1);
        }

        .class-info {
            padding: 1.8rem;
        }

        .class-info h3 {
            margin-bottom: 0.8rem;
            font-size: 1.3rem;
        }

        .class-info p {
            margin-bottom: 1.5rem;
            color: #666;
        }

        /* Trainers Section */
        .trainers-section {
        padding: 5rem 2rem;
        background-color: #f9f9f9;
        text-align: center;
    }
    
    .section-title {
        font-size: 2.5rem;
        color: #1a1a1a;
        margin-bottom: 1rem;
    }
    
    .section-subtitle {
        color: #666;
        margin-bottom: 3rem;
        font-size: 1.2rem;
    }
    
    .trainers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .trainer-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .trainer-card:hover {
        transform: translateY(-10px);
    }
    
    .trainer-image {
        height: 300px;
        overflow: hidden;
    }
    
    .trainer-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .trainer-card:hover .trainer-image img {
        transform: scale(1.05);
    }
    
    .trainer-info {
        padding: 1.5rem;
    }
    
    .trainer-info h3 {
        margin: 0 0 1rem 0;
        color: #1a1a1a;
        font-size: 1.5rem;
    }
    
    .trainer-qualification,
    .trainer-specialty {
        margin: 0.5rem 0;
        color: #555;
        font-size: 0.95rem;
        line-height: 1.5;
    }
    
    @media (max-width: 768px) {
        .trainers-grid {
            grid-template-columns: 1fr;
        }
        
        .trainer-image {
            height: 250px;
        }
    }

        /* Membership Plans */
        .pricing-section {
        padding: 5rem 2rem;
        background-color: #fff;
        text-align: center;
    }
    
    .section-title {
        font-size: 2.5rem;
        color: #1a1a1a;
        margin-bottom: 3rem;
        text-transform: uppercase;
    }
    
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .pricing-card {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        background: white;
        border-top: 5px solid;
    }
    
    .pricing-card:hover {
        transform: translateY(-10px);
    }
    
    .platinum {
        border-color: #e5e4e2;
    }
    
    .gold {
        border-color: #ffd700;
    }
    
    .silver {
        border-color: #c0c0c0;
    }
    
    .pricing-header {
        padding: 1.5rem;
        background-color: #f8f9fa;
    }
    
    .pricing-header h3 {
        margin: 0;
        font-size: 1.8rem;
        color: #1a1a1a;
    }
    
    .membership-type {
        margin: 0.5rem 0 0 0;
        font-weight: bold;
        color: #666;
        font-size: 1rem;
    }
    
    .pricing-body {
        padding: 1.5rem;
    }
    
    .price-list {
        list-style: none;
        padding: 0;
        margin: 0 0 1.5rem 0;
        text-align: left;
    }
    
    .price-list li {
        padding: 0.8rem 0;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
    }
    
    .price-list li:last-child {
        border-bottom: none;
    }
    
    .price-list li span {
        font-weight: bold;
        color: #00b4d8;
    }
    
    .duration {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1.5rem;
    }
    
    .duration p {
        margin: 0.3rem 0;
    }
    
    .duration p:first-child {
        font-weight: bold;
    }
    
    .btn {
        display: inline-block;
        padding: 0.8rem 2rem;
        background-color: #00b4d8;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        transition: background-color 0.3s;
        width: 100%;
        box-sizing: border-box;
    }
    
    .btn:hover {
        background-color: #0096b4;
    }
    
    @media (max-width: 768px) {
        .pricing-grid {
            grid-template-columns: 1fr;
        }
        
        .section-title {
            font-size: 2rem;
        }
    }

        /* Blog Section */
        .blog-section {
        padding: 5rem 2rem;
        background-color: #fff;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .section-title {
        font-size: 2.5rem;
        color: #1a1a1a;
        margin-bottom: 3rem;
        text-align: center;
        text-transform: uppercase;
    }
    
    .blog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }
    
    .blog-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        padding: 2rem;
        border: 1px solid #eee;
    }
    
    .blog-card.highlight {
        background-color: #f8f9fa;
        border-left: 4px solid #00b4d8;
    }
    
    .blog-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .blog-header {
        margin-bottom: 1.5rem;
    }
    
    .blog-header h3 {
        font-size: 1.3rem;
        color: #1a1a1a;
        margin: 0 0 0.5rem 0;
    }
    
    .blog-meta {
        color: #666;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .blog-content p {
        line-height: 1.6;
        color: #555;
        margin: 0;
    }
    
    .blog-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .blog-list li {
        padding: 0.3rem 0;
        color: #555;
    }
    
    .blog-list li strong {
        color: #1a1a1a;
    }
    
    @media (max-width: 768px) {
        .blog-grid {
            grid-template-columns: 1fr;
        }
        
        .section-title {
            font-size: 2rem;
        }
    }

    /* Gallery Section Styling */
    .gallery-section {
        padding: 5rem 2rem;
        background-color: #f8f9fa;
        text-align: center;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .section-title {
        font-size: 2.5rem;
        color: #1a1a1a;
        margin-bottom: 1rem;
        text-transform: uppercase;
    }
    
    .section-subtitle {
        color: #666;
        margin-bottom: 3rem;
        font-size: 1.1rem;
    }
    
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    
    .gallery-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        aspect-ratio: 4/3;
    }
    
    .gallery-item:hover {
        transform: scale(1.03);
    }
    
    .gallery-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.5s ease;
    }
    
    .gallery-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
        padding: 1.5rem 1rem;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .gallery-item:hover .gallery-overlay {
        opacity: 1;
    }
    
    .gallery-caption {
        color: white;
        font-weight: bold;
        text-align: left;
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }
    
    .gallery-item:hover .gallery-caption {
        transform: translateY(0);
    }
    
    /* Lightbox Styling */
    .lightbox {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.9);
        overflow: auto;
    }
    
    .lightbox-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 80vh;
        margin-top: 5vh;
    }
    
    #lightbox-caption {
        margin: auto;
        display: block;
        width: 80%;
        text-align: center;
        color: #fff;
        padding: 10px 0;
    }
    
    .close-btn {
        position: absolute;
        top: 20px;
        right: 30px;
        color: #fff;
        font-size: 35px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close-btn:hover {
        color: #00b4d8;
    }
    
    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: 1fr 1fr;
        }
        
        .section-title {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 480px) {
        .gallery-grid {
            grid-template-columns: 1fr;
        }
    }

        /* Contact Section */
        .contact {
            background-color: var(--light-color);
        }

        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
        }

        .contact-info {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
        }

        .contact-info h3 {
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: var(--dark-color);
        }

        .contact-details {
            margin-bottom: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .contact-text h4 {
            margin-bottom: 0.3rem;
            font-size: 1.1rem;
        }

        .contact-text p {
            color: #666;
        }

        .contact-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--gray-color);
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.2);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        /* Login & Register Forms */
        .auth-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('images/gym-hero.jpg') center/cover no-repeat;
            padding: 2rem;
        }

        .auth-container {
            background: var(--white);
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-color);
        }

        .auth-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark-color);
            position: relative;
        }

        .auth-title::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
            margin: 1rem auto;
        }

        .error {
            color: var(--secondary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 0.8rem;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 5px;
        }

        .auth-link {
            text-align: center;
            margin-top: 2rem;
            color: #666;
        }

        .auth-link a {
            color: var(--primary-color);
            font-weight: 500;
            transition: var(--transition);
        }

        .auth-link a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: var(--white);
            padding: 5rem 0 2rem;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary-color);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-col h3 {
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .footer-col h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
        }

        .footer-col p {
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }

        .footer-links li {
            margin-bottom: 1rem;
        }

        .footer-links a {
            opacity: 0.8;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .footer-links a i {
            margin-right: 0.5rem;
            font-size: 0.8rem;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .footer-bottom p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            z-index: 999;
        }

        .back-to-top.active {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-5px);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .section {
                padding: 4rem 0;
            }

            .section-title {
                font-size: 2.2rem;
            }

            .hero h1 {
                font-size: 3rem;
            }
        }

        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }

            .nav-links {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 80px);
                background: rgba(26, 26, 26, 0.95);
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 2rem;
                transition: var(--transition);
                backdrop-filter: blur(10px);
            }

            .nav-links.active {
                left: 0;
            }

            .auth-buttons {
                display: none;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .contact-container {
                grid-template-columns: 1fr;
            }

            .auth-container {
                padding: 2rem;
            }
        }

        @media (max-width: 576px) {
            .section {
                padding: 3rem 0;
            }

            .section-title {
                font-size: 2rem;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .btn {
                padding: 0.7rem 1.5rem;
                font-size: 0.8rem;
            }

            .auth-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <a href="index.php" class="logo">
            <i class="fas fa-dumbbell"></i>Fit<span>Zone</span>
        </a>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#classes">Classes</a>
            <a href="#trainers">Trainers</a>
            <a href="#membership">Membership</a>
            <a href="#blog">Blog</a>
            <a href="#gallery">Gallery</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="auth-buttons">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1>Transform Your Body, Transform Your Life</h1>
            <p>Join Kurunegala's premier fitness center with state-of-the-art facilities and expert trainers to help you achieve your fitness goals.</p>
            <a href="register.php" class="btn btn-primary">Join Now</a>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section features">
        <div class="container">
            <h2 class="section-title">Why Choose FitZone</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>Modern Equipment</h3>
                    <p>State-of-the-art fitness equipment from leading brands to maximize your workout efficiency.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Expert Trainers</h3>
                    <p>Certified professionals with years of experience to guide you through your fitness journey.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Nutrition Plans</h3>
                    <p>Personalized diet plans to complement your workout routine and maximize results.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Classes -->
    <section id="classes" class="section classes">
        <div class="container">
            <h2 class="section-title">Popular Classes</h2>
            <div class="classes-grid">
                <div class="class-card">
                    <div class="class-img">
                        <img src="images/cardio.jpeg" alt="Cardio Blast">
                    </div>
                    <div class="class-info">
                        <h3>Cardio Blast</h3>
                        <p>High-intensity cardio workout to burn calories and improve endurance.</p>
                        <a href="#" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
                <div class="class-card">
                    <div class="class-img">
                        <img src="images/yoga.jpg" alt="Yoga Flow">
                    </div>
                    <div class="class-info">
                        <h3>Yoga Flow</h3>
                        <p>Improve flexibility and mental clarity with our guided yoga sessions.</p>
                        <a href="#" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
                <div class="class-card">
                    <div class="class-img">
                        <img src="images/strength.jpg" alt="Strength Training">
                    </div>
                    <div class="class-info">
                        <h3>Strength Training</h3>
                        <p>Build muscle and increase strength with our expert-led weight training.</p>
                        <a href="#" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
                <div class="class-card">
                    <div class="class-img">
                        <img src="images/crossfit.jpg" alt="CrossFit">
                    </div>
                    <div class="class-info">
                        <h3>CrossFit</h3>
                        <p>High-intensity functional training to improve all aspects of fitness.</p>
                        <a href="#" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trainers Section -->
    <section id="trainers" class="trainers-section">
    <div class="container">
        <h2 class="section-title">Our Certified Trainers</h2>
        <p class="section-subtitle">Meet our team of professional fitness experts</p>
        
        <div class="trainers-grid">
            <!-- Trainer 1 -->
            <div class="trainer-card">
                <div class="trainer-image">
                    <img src="images/trainer1.jpg" alt="Ayesh Ranasinghe">
                </div>
                <div class="trainer-info">
                    <h3>Ayesh Ranasinghe</h3>
                    <p class="trainer-qualification">Diploma in Sports Strength and Conditioning (SLS)</p>
                    <p class="trainer-specialty">Physical Fitness Instructor (HMG Level-J)</p>
                </div>
            </div>
            
            <!-- Trainer 2 -->
            <div class="trainer-card">
                <div class="trainer-image">
                    <img src="images/trainer2.jpg" alt="Thumesh Almeda">
                </div>
                <div class="trainer-info">
                    <h3>Thumesh Almeda</h3>
                    <p class="trainer-qualification">Physical Fitness Trainer (HMG Learn a-Senior Akira)</p>
                    <p class="trainer-specialty">Certificate for Fitness Trainers (SLS)</p>
                </div>
            </div>
            
            <!-- Trainer 3 -->
            <div class="trainer-card">
                <div class="trainer-image">
                    <img src="images/trainer3.jpg" alt="Dulshan Miyuranga">
                </div>
                <div class="trainer-info">
                    <h3>Dulshan Miyuranga</h3>
                    <p class="trainer-qualification">Certificate for Fitness Trainers (SLS)</p>
                    <p class="trainer-specialty">Specialized in Strength Training</p>
                </div>
            </div>
        </div>
    </div>
    </section>

    <!-- Membership Plans -->
    <section id="membership" class="pricing-section">
    <div class="container">
        <h2 class="section-title">MEMBER PRICING</h2>
        
        <div class="pricing-grid">
            <!-- Platinum Plan -->
            <div class="pricing-card platinum">
                <div class="pricing-header">
                    <h3>PLATINUM</h3>
                    <p class="membership-type">MEMBERSHIP</p>
                </div>
                <div class="pricing-body">
                    <ul class="price-list">
                        <li>Gents - Annual <span>Rs. 65,000</span></li>
                        <li>Ladies - Annual <span>Rs. 55,000</span></li>
                        <li>Couple - Annual <span>Rs. 85,000</span></li>
                    </ul>
                    <div class="duration">
                        <p>Time Duration:</p>
                        <p>4:00am to 12:00 Midnight</p>
                    </div>
                    <a href="register.php?plan=platinum" class="btn">Join Now</a>
                </div>
            </div>
            
            <!-- Gold Plan -->
            <div class="pricing-card gold">
                <div class="pricing-header">
                    <h3>GOLD</h3>
                    <p class="membership-type">MEMBERSHIP</p>
                </div>
                <div class="pricing-body">
                    <ul class="price-list">
                        <li>Gents - Annual <span>Rs. 48,000</span></li>
                        <li>Ladies - Annual <span>Rs. 48,000</span></li>
                    </ul>
                    <div class="duration">
                        <p>Time Duration:</p>
                        <p>4:00am to 4:30pm</p>
                    </div>
                    <a href="register.php?plan=gold" class="btn">Join Now</a>
                </div>
            </div>
            
            <!-- Silver Plan -->
            <div class="pricing-card silver">
                <div class="pricing-header">
                    <h3>SILVER</h3>
                    <p class="membership-type">MEMBERSHIP</p>
                </div>
                <div class="pricing-body">
                    <ul class="price-list">
                        <li>Individual - 6 Months <span>Rs. 45,000</span></li>
                        <li>Individual - 3 Months <span>Rs. 35,000</span></li>
                        <li>Individual - 1 Month <span>Rs. 15,000</span></li>
                    </ul>
                    <div class="duration">
                        <p>Time Duration:</p>
                        <p>4:00am to 12:00 Midnight</p>
                    </div>
                    <a href="register.php?plan=silver" class="btn">Join Now</a>
                </div>
            </div>
        </div>
    </div>
    </section>

    <!-- Featured Blog Posts -->
    <section id="blog" class="blog-section">
    <div class="container">
        <h2 class="section-title">FITNESS RESOURCES</h2>
        
        <div class="blog-grid">
            <!-- Blog Post 1 -->
            <article class="blog-card">
                <div class="blog-header">
                    <h3>Coach:Taking the guesswork out of your workouts.</h3>
                </div>
                <div class="blog-content">
                    <ul class="blog-list">
                        <li><strong>Exercising is for everyone.</strong></li>
                        <li>No matter how many reps you can do or how much time you have in your day, consider this your coaching destination.</li>
                    </ul>
                </div>
            </article>
            
            <!-- Blog Post 2 -->
            <article class="blog-card highlight">
                <div class="blog-header">
                    <h3>Care:Healthy living at your fingertips.</h3>
                    <p class="blog-meta">What you eat, how much you sleep and how you recover all play a part. This is your place for delicious recipes loaded with nutritious ingredients, guidance on healthy living and tips to get the most out of workouts.</p>
                </div>
            </article>
            
            <!-- Blog Post 3 -->
            <article class="blog-card">
                <div class="blog-header">
                    <h3>Connect:We’ve got the A to all of your Qs.</h3>
                </div>
                <div class="blog-content">
                    <ul class="blog-list">
                        <li>Wherever you are and whatever question you have we want to make getting answers easy.</li>
                        <li>This is the place to find them. </li>
                    </ul>
                </div>
            </article>
            
            <!-- Blog Post 4 -->
            <article class="blog-card">
                <div class="blog-content">
                    <p>You’ll find workouts, tips and motivation to make every drop of sweat count. Helpful stuff you need to exercise with confidence.</p>
                </div>
            </article>
            
            <!-- Blog Post 5 -->
            <article class="blog-card">
                <div class="blog-content">
                    <p>Getting healthier doesn’t just happen in the gym.</p>
                </div>
            </article>
            
            <!-- Blog Post 6 -->
            <article class="blog-card">
                <div class="blog-content">
                    <p>Our team regularly adds information about fitness, nutrition and recovery you can use to keep yourself on track to living a healthier life every day.</p>
                </div>
            </article>
        </div>
    </div>
    </section>

    <!-- Gallery Section -->
<section id="gallery" class="gallery-section">
    <div class="container">
        <h2 class="section-title">OUR GYM GALLERY</h2>
        <p class="section-subtitle">Explore Our Fitness Facilities</p>
        
        <div class="gallery-grid">
            <!-- Gallery Item 1 -->
            <div class="gallery-item">
                <img src="images/gallery/gym1.jpg" alt="Gym Facility" class="gallery-image">
                <div class="gallery-overlay">
                    <div class="gallery-caption">Weight Training Area</div>
                </div>
            </div>
            
            <!-- Gallery Item 2 -->
            <div class="gallery-item">
                <img src="images/gallery/gym2.jpg" alt="Cardio Equipment" class="gallery-image">
                <div class="gallery-overlay">
                    <div class="gallery-caption">Cardio Zone</div>
                </div>
            </div>
            
            <!-- Gallery Item 3 -->
            <div class="gallery-item">
                <img src="images/gallery/gym3.jpg" alt="Group Class" class="gallery-image">
                <div class="gallery-overlay">
                    <div class="gallery-caption">Group Fitness</div>
                </div>
            </div>
            
            <!-- Gallery Item 4 -->
            <div class="gallery-item">
                <img src="images/gallery/gym4.jpg" alt="Personal Training" class="gallery-image">
                <div class="gallery-overlay">
                    <div class="gallery-caption">Personal Training</div>
                </div>
            </div>
            
            <!-- Gallery Item 5 -->
            <div class="gallery-item">
                <img src="images/gallery/gym5.jpg" alt="Locker Room" class="gallery-image">
                <div class="gallery-overlay">
                    <div class="gallery-caption">Premium Locker Room</div>
                </div>
            </div>
            
            <!-- Gallery Item 6 -->
            <div class="gallery-item">
                <img src="images/gallery/gym6.jpg" alt="Recovery Area" class="gallery-image">
                <div class="gallery-overlay">
                    <div class="gallery-caption">Recovery Zone</div>
                </div>
            </div>
        </div>
    </div>
    </section>

    <!-- Lightbox Modal -->
    <div id="gallery-lightbox" class="lightbox">
        <span class="close-btn">&times;</span>
        <img class="lightbox-content" id="lightbox-image">
        <div id="lightbox-caption"></div>
    </div>

    <!-- Contact Section -->
    <section id="contact" class="section contact">
        <div class="container">
            <h2 class="section-title">Contact Us</h2>
            <div class="contact-container">
                <form class="contact-form" action="process_contact.php" method="POST">
                    <div class="form-group">
                        <input type="text" class="form-control" name="name" placeholder="Your Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" class="form-control" name="email" placeholder="Your Email" required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" name="subject" placeholder="Subject" required>
                    </div>
                    <div class="form-group">
                        <textarea class="form-control" name="message" placeholder="Your Message" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
                <div class="contact-info">
                    <h3>Get In Touch</h3>
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Address</h4>
                                <p>123 Gym Street, Kurunegala</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Phone</h4>
                                <p>+94 77 123 4567</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Email</h4>
                                <p>info@fitzone.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Hours</h4>
                                <p>Monday-Sunday: 6:00 AM - 10:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>About FitZone</h3>
                    <p>Kurunegala's premier fitness center dedicated to helping you achieve your fitness goals with state-of-the-art facilities and expert trainers.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="#classes"><i class="fas fa-chevron-right"></i> Classes</a></li>
                        <li><a href="#trainers"><i class="fas fa-chevron-right"></i> Trainers</a></li>
                        <li><a href="#membership"><i class="fas fa-chevron-right"></i> Membership</a></li>
                        <li><a href="#blog"><i class="fas fa-chevron-right"></i> Blog</a></li>
                        <li><a href="#gallery"><i class="fas fa-chevron-right"></i> Gallery</a></li>
                        <li><a href="#contact"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Info</h3>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> 123 Gym Street, Kurunegala</a></li>
                        <li><a href="tel:+94771234567"><i class="fas fa-phone-alt"></i> +94 77 123 4567</a></li>
                        <li><a href="mailto:info@fitzone.com"><i class="fas fa-envelope"></i> info@fitzone.com</a></li>
                        <li><a href="#"><i class="fas fa-clock"></i> Mon-Sun: 6:00 AM - 10:00 PM</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> FitZone Fitness Center, Kurunegala. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script>
        // Mobile Navigation
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');

        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            hamburger.innerHTML = navLinks.classList.contains('active') ? 
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });

        // Sticky Navigation on Scroll
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Smooth Scrolling for Navigation Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu if open
                    if (navLinks.classList.contains('active')) {
                        navLinks.classList.remove('active');
                        hamburger.innerHTML = '<i class="fas fa-bars"></i>';
                    }
                }
            });
        });

        // Show/Hide Back to Top Button
        window.addEventListener('scroll', () => {
            const backToTop = document.querySelector('.back-to-top');
            if (window.scrollY > 300) {
                backToTop.classList.add('active');
            } else {
                backToTop.classList.remove('active');
            }
        });

        // Form Validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    let valid = true;
                    const inputs = this.querySelectorAll('input[required], textarea[required]');
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            valid = false;
                            input.style.borderColor = 'red';
                        } else {
                            input.style.borderColor = '';
                        }
                    });
                    
                    if (!valid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            });
        });

        // Animation on Scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.feature-card, .class-card, .trainer-card, .price-card, .blog-card');
            
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.3;
                
                if (elementPosition < screenPosition) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        }

        // Set initial state for animation
        document.querySelectorAll('.feature-card, .class-card, .trainer-card, .price-card, .blog-card').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(50px)';
            element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        });

        // Run animation on load and scroll
        window.addEventListener('load', animateOnScroll);
        window.addEventListener('scroll', animateOnScroll);

        // Lightbox functionality
        document.addEventListener('DOMContentLoaded', function() {

        // Get all gallery items
        const galleryItems = document.querySelectorAll('.gallery-item');
        const lightbox = document.getElementById('gallery-lightbox');
        const lightboxImg = document.getElementById('lightbox-image');
        const lightboxCaption = document.getElementById('lightbox-caption');
        const closeBtn = document.querySelector('.close-btn');
        
        // Add click event to each gallery item
        galleryItems.forEach(item => {
            item.addEventListener('click', function() {
                const imgSrc = this.querySelector('img').src;
                const caption = this.querySelector('.gallery-caption').textContent;
                
                lightboxImg.src = imgSrc;
                lightboxCaption.textContent = caption;
                lightbox.style.display = 'block';
            });
        });
        
        // Close lightbox when clicking X
        closeBtn.addEventListener('click', function() {
            lightbox.style.display = 'none';
        });
        
        // Close lightbox when clicking outside image
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                lightbox.style.display = 'none';
            }
        });
    });

    </script>
</body>
</html>