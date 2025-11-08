    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- jsvectormap css -->
    <link href="{{ asset('assets/libs/jsvectormap/css/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />

    <!--Swiper slider css-->
    <link href="{{ asset('assets/libs/swiper/swiper-bundle.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Layout config Js -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Plugins css -->
    <link href="{{ asset('assets/libs/dropzone/dropzone.css') }}" rel="stylesheet" type="text/css" />
    <!-- Thêm vào phần <head> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Thêm 3 dòng này để nhúng Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <style>
        /* Import Animate.css nếu chưa có */
        @import url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');

        /* Custom Popup Styles */
        .swal-custom-popup {
            border-radius: 20px !important;
            padding: 0 !important;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
        }

        .swal-custom-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            padding: 25px !important;
            margin: 0 !important;
            font-size: 24px !important;
            font-weight: 600 !important;
        }

        .swal-custom-html {
            padding: 30px 20px 20px !important;
        }

        .swal-custom-content {
            text-align: center;
        }

        .swal-icon-wrapper {
            margin-bottom: 20px;
        }

        .swal-icon-wrapper i {
            font-size: 48px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .swal-message {
            font-size: 18px;
            color: #4a5568;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .swal-item-name {
            color: #e53e3e;
            font-weight: 700;
            background: linear-gradient(135deg, #ff6b6b, #ff8787);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .swal-warning {
            background: #fff5f5;
            border-left: 4px solid #fc8181;
            padding: 12px 16px;
            border-radius: 8px;
            color: #c53030;
            font-size: 14px;
            margin-top: 20px;
            text-align: left;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom Actions */
        .swal-custom-actions {
            background: #f7fafc;
            padding: 20px !important;
            margin: 0 !important;
            border-top: 1px solid #e2e8f0;
        }

        /* Custom Buttons */
        .swal-custom-confirm,
        .swal-custom-cancel {
            padding: 12px 28px !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            font-size: 15px !important;
            transition: all 0.3s ease !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 8px !important;
        }

        .swal-custom-cancel {
            background: #e2e8f0 !important;
            color: #4a5568 !important;
            border: none !important;
        }

        .swal-custom-cancel:hover {
            background: #cbd5e0 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Gradient Buttons */
        .btn-gradient-warning {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%) !important;
            color: white !important;
            border: none !important;
        }

        .btn-gradient-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            border: none !important;
        }

        .btn-gradient-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
            color: white !important;
            border: none !important;
        }

        .btn-gradient-warning:hover,
        .btn-gradient-info:hover,
        .btn-gradient-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            filter: brightness(1.1);
        }

        /* Loading Popup */
        .swal-loading-popup {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
        }

        .swal-loading {
            padding: 40px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            margin: 0 auto;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 576px) {
            .swal-custom-popup {
                width: 90% !important;
            }

            .swal-custom-title {
                font-size: 20px !important;
                padding: 20px !important;
            }

            .swal-message {
                font-size: 16px;
            }

            .swal-custom-confirm,
            .swal-custom-cancel {
                padding: 10px 20px !important;
                font-size: 14px !important;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .swal-custom-popup {
                background: #1a202c !important;
            }

            .swal-message {
                color: #cbd5e0;
            }

            .swal-custom-actions {
                background: #2d3748;
                border-top-color: #4a5568;
            }

            .swal-warning {
                background: #2d2d2d;
                color: #fc8181;
            }
        }
    </style>
