<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="index.html" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/logo-dark.png') }}" alt="" height="17">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="index.html" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/logo-white.png') }}" alt="" height="60">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span data-key="t-menu">Sản Phẩm</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarDashboards" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarDashboards">
                        <i class="lab la-buffer"></i> <span data-key="t-dashboards">Quản lí danh mục</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarDashboards">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.categories.index') }}" class="nav-link" data-key="t-analytics">
                                    Danh sách danh mục
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.categories.create') }}" class="nav-link"
                                    data-key="t-analytics"> Thêm mới danh mục
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.categories.trash') }}" class="nav-link" data-key="t-analytics">
                                    Danh sách danh mục đã xóa
                                </a>
                            </li>
                        </ul>
                    </div>
                </li> <!-- end Dashboard Menu -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarApps" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarApps">
                        <i class="ri-apps-2-line"></i> <span data-key="t-apps">Quản lí sản phẩm</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarApps">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.products.index') }}" class="nav-link" data-key="t-analytics">
                                    Danh sách sản phẩm
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.products.create') }}" class="nav-link" data-key="t-analytics">
                                    Thêm mới sản phẩm
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.products.trash') }}" class="nav-link" data-key="t-analytics">
                                    Danh sách sản phẩm đã xóa
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAppss" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarAppss">
                        <i class="ri-apps-2-line"></i> <span data-key="t-apps">Quản lí sản phẩm biến thể</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAppss">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.product_variants.index') }}" class="nav-link"
                                    data-key="t-analytics">
                                    Danh sách biến thể sản phẩm
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.product_variants.create') }}" class="nav-link"
                                    data-key="t-analytics"> Thêm mới biến thể sản phẩm
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.product-attributes.index') }}" class="nav-link"
                                    data-key="t-analytics"> Danh sách thuộc tính sản phẩm
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.product-attributes.create') }}" class="nav-link"
                                    data-key="t-analytics"> Thêm mới thuộc tính sản phẩm
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.product_variants.trash') }}" class="nav-link"
                                    data-key="t-analytics"> Danh sách biến thể đã xóa
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarInventory" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarInventory">
                        <i class="ri-store-3-line"></i>
                        <span data-key="t-inventory">Quản lý kho hàng</span>
                        @if (isset($lowStockCount) && $lowStockCount > 0)
                            <span class="badge badge-pill bg-danger float-end">{{ $lowStockCount }}</span>
                        @endif
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarInventory">
                        <ul class="nav nav-sm flex-column">
                            {{-- Tổng quan --}}
                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.dashboard') }}" class="nav-link">
                                    <i class="ri-dashboard-line me-1"></i> Tổng quan kho
                                </a>
                            </li>

                            {{-- Tồn kho --}}
                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.index') }}" class="nav-link">
                                    <i class="ri-stack-line me-1"></i> Tồn kho hiện tại
                                </a>
                            </li>

                            {{-- Nhập/Xuất kho dropdown con --}}
                            <li class="nav-item">
                                <a href="#sidebarInOut" class="nav-link" data-bs-toggle="collapse" role="button"
                                    aria-expanded="false" aria-controls="sidebarInOut">
                                    <i class="ri-exchange-line me-1"></i> Nhập / Xuất kho
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarInOut">
                                    <ul class="nav nav-sm flex-column ms-3">
                                        <li class="nav-item">
                                            <a href="{{ route('admin.inventory.import') }}" class="nav-link">
                                                <i class="ri-download-2-line me-1"></i> Nhập kho
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('admin.inventory.export') }}" class="nav-link">
                                                <i class="ri-upload-2-line me-1"></i> Xuất kho
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('admin.inventory.transfer') }}" class="nav-link">
                                                <i class="ri-arrow-left-right-line me-1"></i> Chuyển kho
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            {{-- Kiểm kê --}}
                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.stocktake') }}" class="nav-link">
                                    <i class="ri-file-list-3-line me-1"></i> Kiểm kê kho
                                </a>
                            </li>

                            {{-- Lịch sử giao dịch --}}
                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.transactions') }}" class="nav-link">
                                    <i class="ri-history-line me-1"></i> Lịch sử giao dịch
                                </a>
                            </li>

                            {{-- Quản lý kho --}}
                            <li class="nav-item">
                                <a href="{{ route('admin.warehouses.index') }}" class="nav-link">
                                    <i class="ri-home-4-line me-1"></i> Danh sách kho
                                </a>
                            </li>

                            {{-- Báo cáo --}}
                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.reports') }}" class="nav-link">
                                    <i class="ri-bar-chart-box-line me-1"></i> Báo cáo kho
                                </a>
                            </li>

                            {{-- Cài đặt --}}
                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.settings') }}" class="nav-link">
                                    <i class="ri-settings-3-line me-1"></i> Cài đặt kho
                                </a>
                            </li>
                        </ul>
                    </div>
                    <!-- end Dashboard Menu -->

                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">Giao diện/Truyền
                        Thông</span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAuth" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarAuth">
                        <i class="ri-account-circle-line"></i> <span data-key="t-authentication">Quản lí banner</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAuth">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.banners.index') }}" class="nav-link"
                                    data-key="t-analytics">
                                    Danh sách banner
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.banners.create') }}" class="nav-link"
                                    data-key="t-analytics"> Thêm mới banner
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.banners.trash') }}" class="nav-link"
                                    data-key="t-analytics"> Danh sách banner đã xóa
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarPages" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarPages">
                        <i class="ri-pages-line"></i> <span data-key="t-pages">Quản lí bài viết</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarPages">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.posts.index') }}" class="nav-link"
                                    data-key="t-analytics">
                                    Danh sách bài viết
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.posts.create') }}" class="nav-link"
                                    data-key="t-analytics"> Thêm mới bài viết
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.posts.trash') }}" class="nav-link"
                                    data-key="t-analytics"> Danh sách bài viết đã xóa
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarLanding" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarLanding">
                        <i class="ri-rocket-line"></i> <span data-key="t-landing">Quản lí trang tĩnh</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarLanding">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="landing.html" class="nav-link" data-key="t-one-page"> One Page </a>
                            </li>
                            <li class="nav-item">
                                <a href="nft-landing.html" class="nav-link" data-key="t-nft-landing"> NFT Landing
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="job-landing.html" class="nav-link" data-key="t-job">Job</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-components">Khách hàng và đơn
                        hàng</span>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarUI" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarUI">
                        <i class="ri-pencil-ruler-2-line"></i> <span data-key="t-base-ui">Quản lí người dùng</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarUI">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.users.index') }}" class="nav-link" data-key="t-analytics">
                                    Danh sách người dùng
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.users.create') }}" class="nav-link" data-key="t-analytics">
                                    Thêm mới người dùng
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.users.trash') }}" class="nav-link" data-key="t-analytics">
                                    Danh sách người dùng đã xóa
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAdvanceUI" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarAdvanceUI">
                        <i class="ri-stack-line"></i> <span data-key="t-advance-ui">Quản lí đơn hàng</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAdvanceUI">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="advance-ui-sweetalerts.html" class="nav-link"
                                    data-key="t-sweet-alerts">Sweet
                                    Alerts</a>
                            </li>
                            <li class="nav-item">
                                <a href="advance-ui-nestable.html" class="nav-link"
                                    data-key="t-nestable-list">Nestable
                                    List</a>
                            </li>
                            <li class="nav-item">
                                <a href="advance-ui-scrollbar.html" class="nav-link"
                                    data-key="t-scrollbar">Scrollbar</a>
                            </li>
                            <li class="nav-item">
                                <a href="advance-ui-animation.html" class="nav-link"
                                    data-key="t-animation">Animation</a>
                            </li>
                            <li class="nav-item">
                                <a href="advance-ui-tour.html" class="nav-link" data-key="t-tour">Tour</a>
                            </li>
                            <li class="nav-item">
                                <a href="advance-ui-swiper.html" class="nav-link" data-key="t-swiper-slider">Swiper
                                    Slider</a>
                            </li>
                            <li class="nav-item">
                                <a href="advance-ui-ratings.html" class="nav-link" data-key="t-ratings">Ratings</a>
                            </li>
                            <li class="nav-item">
                                <a href="advance-ui-highlight.html" class="nav-link"
                                    data-key="t-highlight">Highlight</a>
                            </li>
                            <li class="nav-item">
                                <a href="advance-ui-scrollspy.html" class="nav-link"
                                    data-key="t-scrollSpy">ScrollSpy</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarForms" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarForms">
                        <i class="ri-file-list-3-line"></i> <span data-key="t-forms">Quản lí đánh giá</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarForms">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="forms-elements.html" class="nav-link" data-key="t-basic-elements">Basic
                                    Elements</a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-select.html" class="nav-link" data-key="t-form-select"> Form Select
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-checkboxs-radios.html" class="nav-link"
                                    data-key="t-checkboxs-radios">Checkboxs & Radios</a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-pickers.html" class="nav-link" data-key="t-pickers"> Pickers </a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-masks.html" class="nav-link" data-key="t-input-masks">Input Masks</a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-advanced.html" class="nav-link" data-key="t-advanced">Advanced</a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-range-sliders.html" class="nav-link" data-key="t-range-slider"> Range
                                    Slider </a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-validation.html" class="nav-link"
                                    data-key="t-validation">Validation</a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-wizard.html" class="nav-link" data-key="t-wizard">Wizard</a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-editors.html" class="nav-link" data-key="t-editors">Editors</a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-file-uploads.html" class="nav-link" data-key="t-file-uploads">File
                                    Uploads</a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-layouts.html" class="nav-link" data-key="t-form-layouts">Form
                                    Layouts</a>
                            </li>
                            <li class="nav-item">
                                <a href="forms-select2.html" class="nav-link" data-key="t-select2">Select2</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-components">Marketing</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarTables" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarTables">
                        <i class="ri-layout-grid-line"></i> <span data-key="t-tables">Quản lí mã giảm giá</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarTables">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="tables-basic.html" class="nav-link" data-key="t-basic-tables">Basic
                                    Tables</a>
                            </li>
                            <li class="nav-item">
                                <a href="tables-gridjs.html" class="nav-link" data-key="t-grid-js">Grid Js</a>
                            </li>
                            <li class="nav-item">
                                <a href="tables-listjs.html" class="nav-link" data-key="t-list-js">List Js</a>
                            </li>
                            <li class="nav-item">
                                <a href="tables-datatables.html" class="nav-link"
                                    data-key="t-datatables">Datatables</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarCharts" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarCharts">
                        <i class="ri-pie-chart-line"></i> <span data-key="t-charts">Quản lí chương trình khuyến
                            mãi</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarCharts">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.promotions.index') }}" class="nav-link"
                                    data-key="t-analytics">
                                    Danh sách chương trình
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.promotions.create') }}" class="nav-link"
                                    data-key="t-analytics"> Thêm mới chương trình
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.promotions.trash') }}" class="nav-link"
                                    data-key="t-analytics">
                                    Danh sách chương trình đã xóa
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-components">Thống kê</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarIcons" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarIcons">
                        <i class="ri-compasses-2-line"></i> <span data-key="t-icons">Icons</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarIcons">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="icons-remix.html" class="nav-link"><span data-key="t-remix">Remix</span>
                                    <span class="badge badge-pill bg-info">v3.5</span></a>
                            </li>
                            <li class="nav-item">
                                <a href="icons-boxicons.html" class="nav-link"><span
                                        data-key="t-boxicons">Boxicons</span> <span
                                        class="badge badge-pill bg-info">v2.1.4</span></a>
                            </li>
                            <li class="nav-item">
                                <a href="icons-materialdesign.html" class="nav-link"><span
                                        data-key="t-material-design">Material Design</span> <span
                                        class="badge badge-pill bg-info">v7.2.96</span></a>
                            </li>
                            <li class="nav-item">
                                <a href="icons-lineawesome.html" class="nav-link" data-key="t-line-awesome">Line
                                    Awesome</a>
                            </li>
                            <li class="nav-item">
                                <a href="icons-feather.html" class="nav-link"><span
                                        data-key="t-feather">Feather</span> <span
                                        class="badge badge-pill bg-info">v4.29</span></a>
                            </li>
                            <li class="nav-item">
                                <a href="icons-crypto.html" class="nav-link"> <span data-key="t-crypto-svg">Crypto
                                        SVG</span></a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarMaps" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarMaps">
                        <i class="ri-map-pin-line"></i> <span data-key="t-maps">Maps</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarMaps">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="maps-google.html" class="nav-link" data-key="t-google">
                                    Google
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="maps-vector.html" class="nav-link" data-key="t-vector">
                                    Vector
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="maps-leaflet.html" class="nav-link" data-key="t-leaflet">
                                    Leaflet
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarMultilevel" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarMultilevel">
                        <i class="ri-share-line"></i> <span data-key="t-multi-level">Multi Level</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarMultilevel">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="#" class="nav-link" data-key="t-level-1.1"> Level 1.1 </a>
                            </li>
                            <li class="nav-item">
                                <a href="#sidebarAccount" class="nav-link" data-bs-toggle="collapse" role="button"
                                    aria-expanded="false" aria-controls="sidebarAccount" data-key="t-level-1.2">
                                    Level
                                    1.2
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarAccount">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="#" class="nav-link" data-key="t-level-2.1"> Level 2.1 </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#sidebarCrm" class="nav-link" data-bs-toggle="collapse"
                                                role="button" aria-expanded="false" aria-controls="sidebarCrm"
                                                data-key="t-level-2.2"> Level 2.2
                                            </a>
                                            <div class="collapse menu-dropdown" id="sidebarCrm">
                                                <ul class="nav nav-sm flex-column">
                                                    <li class="nav-item">
                                                        <a href="#" class="nav-link" data-key="t-level-3.1">
                                                            Level 3.1
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a href="#" class="nav-link" data-key="t-level-3.2">
                                                            Level 3.2
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
