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
    <link href="{{ asset('assets/libs/dropzone/dropzone.css')}}" rel="stylesheet" type="text/css" />
    <!-- Thêm vào phần <head> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Thêm 3 dòng này để nhúng Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Nền hộp thoại */
.swal2-popup-custom {
    border-radius: 16px !important;
    padding: 20px !important;
}

/* Nút Xóa */
.swal2-confirm-custom {
    background-color: #f44336 !important;
    color: white !important;
    border-radius: 8px !important;
    padding: 8px 20px !important;
    font-weight: bold !important;
    border: none !important;
    margin: 0 5px;
    transition: background-color 0.2s;
}
.swal2-confirm-custom:hover {
    background-color: #d32f2f !important;
}

/* Nút Hủy */
.swal2-cancel-custom {
    background-color: #e0e0e0 !important;
    color: #333 !important;
    border-radius: 8px !important;
    padding: 8px 20px !important;
    font-weight: bold !important;
    border: none !important;
    margin: 0 5px;
    transition: background-color 0.2s;
}
.swal2-cancel-custom:hover {
    background-color: #bdbdbd !important;
}

    </style>
    <!-- Thêm trước </body> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
