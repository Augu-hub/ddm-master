<template>
    <VerticalLayout>
        <PageTitle title="Product Details" subtitle="eCommerce" />
        <b-row>
            <b-col xl="5" lg="12">
                <b-card no-body class="bg-body shadow-none">
                    <b-card-body>
                        <Swiper :modules="[FreeMode, Navigation, Thumbs, Autoplay]" :thumbs="{ swiper: thumbsSwiper }" :autoplay="{ delay: 5000 }">
                            <SwiperSlide v-for="image in images" class="text-center" :key="image">
                                <img :src="image" alt="" class="img-fluid bg-body rounded shadow-none" />
                            </SwiperSlide>
                        </Swiper>

                        <Swiper
                            :modules="[FreeMode, Navigation, Thumbs]"
                            @swiper="setThumbsSwiper"
                            :freeMode="true"
                            :slidesPerView="images.length"
                            :spaceBetween="10"
                            :watchSlidesProgress="true"
                        >
                            <SwiperSlide
                                v-for="image in images"
                                :key="image"
                                class="bg-light-subtle d-flex justify-content-center h-auto rounded border"
                            >
                                <img :src="image" alt="" class="d-block avatar-xl" />
                            </SwiperSlide>
                        </Swiper>
                    </b-card-body>

                    <b-card-footer class="rounded p-0">
                        <div class="bg-dark-gradient mt-3 rounded p-3 text-center shadow">
                            <p class="fs-20 fw-semibold mb-1 text-white">Enjoy an Additional 20% Discount on Your Purchase</p>
                            <p class="text-white-50 mb-1">On Purchase Of 2+ Style</p>
                            <p class="text-white-50 mb-0">USE CODE : <span class="text-warning fw-bold">OCEN24</span></p>
                        </div>
                        <div class="bg-body-secondary mt-3 rounded p-3 shadow">
                            <h4 class="text-dark mb-3">Offer Expires In :</h4>
                            <div class="bg-warning-subtle border-warning-subtle rounded border p-2">
                                <b-row class="text-xxl-center">
                                    <b-col class="border-end border-warning-subtle">
                                        <h3 id="days" class="fw-bold fs-18 text-dark">10</h3>
                                        <p class="mb-0">Days</p>
                                    </b-col>
                                    <b-col class="border-end border-warning-subtle">
                                        <h3 id="hours" class="fw-bold fs-18 text-dark">09</h3>
                                        <p class="mb-0">Hours</p>
                                    </b-col>
                                    <b-col class="border-end border-warning-subtle">
                                        <h3 id="minutes" class="fw-bold fs-18 text-dark">30</h3>
                                        <p class="mb-0">Minutes</p>
                                    </b-col>
                                    <b-col>
                                        <h3 id="seconds" class="fw-bold fs-18 text-dark">70</h3>
                                        <p class="mb-0">Seconds</p>
                                    </b-col>
                                </b-row>
                            </div>
                        </div>
                    </b-card-footer>
                    <span class="position-absolute z-1 end-0 top-0 p-5 pt-0">
                        <div data-toggler="on">
                            <button type="button" class="btn btn-icon btn-light rounded-circle" data-toggler-on>
                                <Icon icon="solar:heart-angle-bold-duotone" class="fs-22 text-danger" />
                            </button>
                            <button type="button" class="btn btn-icon btn-light rounded-circle d-none" data-toggler-off>
                                <Icon icon="solar:heart-angle-bold-duotone" class="fs-22" data-toggler-off />
                            </button>
                        </div>
                    </span>
                    <span class="position-absolute z-1 start-0 top-0 p-5 pt-2">
                        <span class="badge bg-danger fs-14">On Deal</span>
                    </span>
                </b-card>
            </b-col>

            <b-col xl="7" lg="12">
                <b-card no-body>
                    <b-card-body>
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="badge bg-success-subtle text-success fs-13 rounded-pill px-2 py-1">New</span>
                            </div>
                            <div class="d-inline-flex align-items-center justify-content-end fs-16 flex-grow-1">
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="fs-14 ms-1">23k Reviews </span>
                            </div>
                        </div>
                        <div class="mb-1 mt-3">
                            <a href="#!" class="text-dark fs-20 fw-medium">Minetta Rattan Swivel Luxury Green Premium Lounge Chair</a>
                        </div>
                        <p class="text-muted fw-medium fs-14 mb-1"><span class="text-dark">Menufechar : </span> Premium Furniture</p>
                        <p class="text-muted fw-medium fs-14 mb-1"><span class="text-dark">Article : </span> CR63541</p>
                        <p class="text-muted fw-medium fs-14 mb-1"><span class="text-dark">Sold Items : </span> 76k</p>
                        <p class="text-muted fw-medium fs-14 mb-0"><span class="text-dark">Product Code : </span> CD4671CR</p>

                        <h2 class="fw-bold text-dark my-4">$300.00 <span class="text-muted fs-14 fw-medium">/ 20% Off</span></h2>
                        <div class="d-flex align-items-center mb-2 mt-3 flex-wrap gap-2" role="group" aria-label="Basic checkbox toggle button group">
                            <p class="text-dark fw-semibold fs-15 mb-0">Colors :</p>
                            <input type="checkbox" class="btn-check" id="color-dark2" />
                            <label class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center" for="color-dark2">
                                <i class="ti ti-circle-filled fs-28 rounded-circle text-success border"></i
                            ></label>

                            <input type="checkbox" class="btn-check" id="color-yellow2" />
                            <label class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center" for="color-yellow2">
                                <i class="ti ti-circle-filled fs-28 rounded-circle text-warning border"></i
                            ></label>

                            <input type="checkbox" class="btn-check" id="color-white2" />
                            <label class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center" for="color-white2">
                                <i class="ti ti-circle-filled fs-28 rounded-circle text-primary border"></i
                            ></label>

                            <input type="checkbox" class="btn-check" id="color-info" />
                            <label class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center" for="color-info">
                                <i class="ti ti-circle-filled fs-28 rounded-circle text-info border"></i
                            ></label>
                        </div>
                        <div class="d-flex align-items-center my-3 flex-wrap gap-2">
                            <p class="text-dark fw-semibold fs-15 mb-0">Stock :</p>
                            <div>
                                <p class="text-success fw-semibold fs-15 mb-0"><i class="ti ti-checks"></i> In Stock</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center my-3 flex-wrap gap-2">
                            <p class="text-dark fw-semibold fs-15 mb-0">Quantity :</p>
                            <div data-touchspin class="input-step bg-body-secondary rounded-pill d-inline-flex mt-1 overflow-visible border p-1">
                                <button type="button" class="minus bg-light text-dark rounded-circle fs-20 lh-1 h-100 border-0">-</button>
                                <input
                                    type="number"
                                    class="text-dark bg-body-secondary h-100 rounded border-0 text-center"
                                    value="1"
                                    min="0"
                                    max="100"
                                />
                                <button type="button" class="plus bg-light text-dark rounded-circle fs-20 lh-1 h-100 border-0">+</button>
                            </div>
                        </div>
                        <h4 class="text-dark fw-medium">Description :</h4>
                        <p class="mb-1">
                            Bring Axis home and watch life revolve around it. This 2-seat sofa offers exceptional durability for family rooms and
                            casual living rooms, featuring track arms that create a clean look. Low back cushions and deep seats encourage lounging
                            with family and friends.
                        </p>
                        <p class="mb-1">
                            An exceptional occasional chair with slim arm rests, styled with a reclined back for supreme comfort. The metal tipped
                            flaring legs add a decorative flourish to this elegant classic. Also available with a quilted fabric back (60-0775)
                        </p>
                        <a href="#!" class="link-primary">Read More...</a>
                        <h4 class="text-dark fw-medium mt-3">Details :</h4>
                        <ul class="d-flex flex-column fs-14 mb-0 gap-1">
                            <li>Doweled mortise and tenon frame in solid Mahogany from certified source.</li>
                            <li>Solid certified Mahogany.</li>
                            <li>Available in a selection of finishes.</li>
                            <li>Tight seat undersupport with high-density upholstery fill.</li>
                        </ul>

                        <h4 class="text-dark fw-medium mb-2 mt-3 pb-1">Additional Information :</h4>
                        <div class="rounded border border-dashed p-2 text-center">
                            <b-row>
                                <b-col lg="3" cols="4" class="border-end">
                                    <p class="text-muted fw-medium fs-14 mb-0"><span class="text-dark">Arm Width : </span> 54 cm</p>
                                </b-col>
                                <b-col lg="3" cols="4" class="border-end">
                                    <p class="text-muted fw-medium fs-14 mb-0"><span class="text-dark">Seat Width : </span> 67 cm</p>
                                </b-col>
                                <b-col lg="3" cols="4" class="border-end">
                                    <p class="text-muted fw-medium fs-14 mb-0"><span class="text-dark">Seat Depth : </span> 54 cm</p>
                                </b-col>
                                <b-col lg="3" cols="4" class="border-end">
                                    <p class="text-muted fw-medium fs-14 mb-0"><span class="text-dark">Seat Height : </span> 40 cm</p>
                                </b-col>
                            </b-row>
                        </div>
                    </b-card-body>
                    <b-card-footer class="border-top border-dashed">
                        <b-row class="g-2">
                            <b-col lg="3">
                                <a href="#!" class="btn btn-primary d-flex align-items-center w-100 gap-1">
                                    <Icon icon="solar:cart-large-2-bold" class="fs-16 align-middle" />
                                    Add to Bag</a
                                >
                            </b-col>
                            <b-col lg="3">
                                <a href="#!" class="btn btn-success d-flex align-items-center w-100 gap-1">
                                    <Icon icon="solar:bag-check-bold" class="fs-16 align-middle" />
                                    Buy Now</a
                                >
                            </b-col>
                            <b-col lg="3">
                                <a href="#!" class="btn btn-outline-danger d-flex align-items-center w-75 gap-1">
                                    <Icon icon="solar:heart-bold" class="fs-16 align-middle" />
                                    Wishlist</a
                                >
                            </b-col>
                        </b-row>
                    </b-card-footer>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="12">
                <b-card no-body>
                    <b-tabs content-class="m-3">
                        <b-tab title-item-class="p-2 pb-0">
                            <template v-slot:title>
                                <span class="d-block d-sm-none"><Icon icon="solar:notebook-bold" class="fs-20" /></span>
                                <span class="d-none d-sm-block"><Icon icon="solar:notebook-bold" class="fs-14 me-1 align-middle" /> Description</span>
                            </template>

                            <b-row>
                                <b-col lg="6">
                                    <p>
                                        Modern designer chair with a frame made of a metal profile. The minimalist style will work in any modern and
                                        loft interior. The furniture is characterized by high durability and solid workmanship, so you can be sure
                                        that it will serve you for many years.
                                    </p>
                                </b-col>
                            </b-row>
                            <b-row class="g-4">
                                <b-col lg="6">
                                    <b-card-title tag="h4" class="my-2">Dimension :</b-card-title>
                                    <b-table-simple responsive class="mb-0">
                                        <b-tbody>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Width :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">54 cm</b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Length :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">50 cm</b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Seat Width :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">67 cm</b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Seat Length :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">65 cm</b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Seat Height :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">40 cm</b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Backrest Height :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">50 cm</b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Armrest Height From THe Ground :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">58 cm</b-td>
                                            </b-tr>
                                        </b-tbody>
                                    </b-table-simple>
                                </b-col>
                                <b-col lg="6">
                                    <b-card-title tag="h4" class="my-2">Material :</b-card-title>
                                    <b-table-simple responsive class="mb-0">
                                        <b-tbody>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Material Seat :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">Velor Fabric</b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Material Base :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">Steel Powder Coated</b-td>
                                            </b-tr>
                                        </b-tbody>
                                    </b-table-simple>
                                    <b-card-title tag="h4" class="mb-1 mt-3">Colors :</b-card-title>
                                    <b-table-simple responsive class="mb-0">
                                        <b-tbody>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Seat Color :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">Sky Blue</b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="px-0">
                                                    <p class="d-flex align-items-center mb-0 gap-1">Base Color :</p>
                                                </b-td>
                                                <b-td class="text-dark fw-medium px-0 text-end">Wooden Brown</b-td>
                                            </b-tr>
                                        </b-tbody>
                                    </b-table-simple>
                                </b-col>
                            </b-row>
                        </b-tab>

                        <b-tab title-item-class="p-2 pb-0">
                            <template v-slot:title>
                                <span class="d-block d-sm-none"><Icon icon="solar:chat-dots-bold" class="fs-20" /></span>
                                <span class="d-none d-sm-block"><Icon icon="solar:chat-dots-bold" class="fs-14 me-1 align-middle" /> Review</span>
                            </template>

                            <b-row class="g-2">
                                <b-col xl="6" lg="12">
                                    <div class="pe-lg-3">
                                        <b-card-title tag="h4" class="">
                                            <Icon icon="solar:box-bold-duotone" />
                                            Product Rating
                                        </b-card-title>
                                        <b-row class="align-items-center my-4 text-center">
                                            <b-col lg="4">
                                                <h2 class="text-dark fw-semibold d-flex align-items-center justify-content-center gap-1">
                                                    4.5
                                                    <Icon icon="solar:star-bold" class="text-warning" />
                                                </h2>
                                                <p class="fs-5 mb-0">Best Review</p>
                                            </b-col>
                                            <b-col lg="8">
                                                <div class="d-flex align-items-center gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">5 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 90%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">4525</h5>
                                                </div>
                                                <div class="d-flex align-items-center my-3 gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">4 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 70%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">2343</h5>
                                                </div>
                                                <div class="d-flex align-items-center my-3 gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">3 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 30%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">221</h5>
                                                </div>
                                                <div class="d-flex align-items-center my-3 gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">2 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 20%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">109</h5>
                                                </div>
                                                <div class="d-flex align-items-center mt-3 gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">1 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 10%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">129</h5>
                                                </div>
                                            </b-col>
                                        </b-row>

                                        <b-card-title tag="h4" class="mb-3">Customer Upload Photos :</b-card-title>
                                        <div class="d-flex align-items-center flex-wrap gap-2 text-center">
                                            <div>
                                                <a href="#!">
                                                    <img :src="product3" alt="" class="avatar-xl bg-light-subtle rounded border" />
                                                </a>
                                            </div>
                                            <div>
                                                <a href="#!">
                                                    <img :src="product12" alt="" class="avatar-xl bg-light-subtle rounded border" />
                                                </a>
                                            </div>
                                            <div>
                                                <a href="#!">
                                                    <img :src="product11" alt="" class="avatar-xl bg-light-subtle rounded border" />
                                                </a>
                                            </div>
                                            <div>
                                                <a href="#!">
                                                    <img :src="product10" alt="" class="avatar-xl bg-light-subtle rounded border" />
                                                </a>
                                            </div>
                                            <div class="avatar-xl bg-light d-flex justify-content-center align-items-center rounded border">
                                                <a href="#!" class="fs-16 fw-semibold text-dark"> 44+ </a>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
                                            <b-card-title tag="h4" class="">Customer Reviews (2567) :</b-card-title>
                                            <div>
                                                <a href="#!" class="link-primary fw-medium text-decoration-underline">View All</a>
                                            </div>
                                        </div>
                                        <b-row>
                                            <b-col lg="12">
                                                <div class="d-flex gap-3">
                                                    <div>
                                                        <span
                                                            class="badge bg-warning-subtle text-dark fs-14 d-flex align-items-center justify-content-center mb-2 gap-1 px-2 py-1"
                                                            >4.5 <Icon icon="solar:star-bold" class="text-warning fs-16"
                                                        /></span>
                                                        <a href="#!">
                                                            <img :src="product10" alt="" class="avatar-xl bg-light-subtle rounded border" />
                                                        </a>
                                                    </div>
                                                    <div>
                                                        <p class="fs-15 d-flex fw-semibold gap-2">
                                                            Ruth L. Garner<span class="text-muted fs-14 fw-normal">|</span
                                                            ><span class="text-muted fs-14 fw-normal">11 November 2023</span>
                                                        </p>
                                                        <p class="mb-0">
                                                            The design of the chair is sleek and modern, fitting perfectly with the aesthetic of my
                                                            home office. The black mesh backrest not only looks stylish but also provides excellent
                                                            reachability, preventing any discomfort during long working hours.
                                                        </p>
                                                        <div class="d-flex align-items-center mb-1 mt-2 gap-2">
                                                            <ul class="d-flex text-warning fs-18 list-unstyled m-0">
                                                                <li>
                                                                    <i class="ti ti-star-filled"></i>
                                                                </li>
                                                                <li>
                                                                    <i class="ti ti-star-filled"></i>
                                                                </li>
                                                                <li>
                                                                    <i class="ti ti-star-filled"></i>
                                                                </li>
                                                                <li>
                                                                    <i class="ti ti-star-filled"></i>
                                                                </li>
                                                                <li>
                                                                    <i class="ti ti-star-half-filled"></i>
                                                                </li>
                                                            </ul>
                                                            <p class="fw-medium text-dark fs-14 mb-0">4.5 / 5 Best Review</p>
                                                        </div>
                                                        <div class="mt-3">
                                                            <a href="#!" class="fs-14 link-reset me-3"><i class="ti ti-thumb-up fs-18"></i> 2543</a>
                                                            <a href="#!" class="fs-14 link-reset me-3"><i class="ti ti-thumb-down fs-18"></i> 129</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr class="my-3" />
                                                <div class="d-flex gap-3">
                                                    <div>
                                                        <span
                                                            class="badge bg-warning-subtle text-dark fs-14 d-flex align-items-center justify-content-center mb-2 gap-1 px-2 py-1"
                                                            >4.2 <Icon icon="solar:star-bold" class="text-warning fs-16"
                                                        /></span>
                                                        <a href="#!">
                                                            <img :src="product10" alt="" class="avatar-xl bg-light-subtle d-none rounded border" />
                                                        </a>
                                                    </div>
                                                    <div>
                                                        <p class="fs-15 d-flex fw-semibold gap-2">
                                                            Michelle M. Kellerman<span class="text-muted fs-14 fw-normal">|</span
                                                            ><span class="text-muted fs-14 fw-normal">20 March 2024</span>
                                                        </p>
                                                        <p class="mb-0">
                                                            One of the standout features of this chair is its adjustability. The chair offers a wide
                                                            range of adjustments, including seat height, armrest height, and tilt tension. The lumbar
                                                            support can be easily adjusted to fit the natural curve
                                                        </p>
                                                        <div class="d-flex align-items-center mb-1 mt-2 gap-2">
                                                            <ul class="d-flex text-warning fs-18 list-unstyled m-0">
                                                                <li>
                                                                    <i class="ti ti-star-filled"></i>
                                                                </li>
                                                                <li>
                                                                    <i class="ti ti-star-filled"></i>
                                                                </li>
                                                                <li>
                                                                    <i class="ti ti-star-filled"></i>
                                                                </li>
                                                                <li>
                                                                    <i class="ti ti-star-filled"></i>
                                                                </li>
                                                                <li>
                                                                    <i class="ti ti-star-half-filled"></i>
                                                                </li>
                                                            </ul>
                                                            <p class="fw-medium text-dark fs-14 mb-0">4.2 / 5 Best Review</p>
                                                        </div>
                                                        <div class="mt-3">
                                                            <a href="#!" class="fs-14 link-reset me-3"><i class="ti ti-thumb-up fs-18"></i> 1323</a>
                                                            <a href="#!" class="fs-14 link-reset me-3"><i class="ti ti-thumb-down fs-18"></i> 34</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </b-col>
                                        </b-row>
                                    </div>
                                </b-col>
                                <div class="col-xl-6 col-lg-12 border-start">
                                    <div class="ps-lg-3">
                                        <b-card-title tag="h4" class="">
                                            <Icon icon="solar:shop-2-bold" />
                                            Shop Rating
                                        </b-card-title>
                                        <div class="row align-items-center my-4 text-center">
                                            <b-col lg="4">
                                                <h2 class="text-dark fw-semibold d-flex align-items-center justify-content-center gap-1">
                                                    4.2
                                                    <Icon icon="solar:star-bold" class="text-warning" />
                                                </h2>
                                                <p class="fs-5 mb-0">Best Review</p>
                                            </b-col>
                                            <b-col lg="8">
                                                <div class="d-flex align-items-center gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">5 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 70%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">3641</h5>
                                                </div>
                                                <div class="d-flex align-items-center my-3 gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">4 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 65%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">2912</h5>
                                                </div>
                                                <div class="d-flex align-items-center my-3 gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">3 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 60%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">321</h5>
                                                </div>
                                                <div class="d-flex align-items-center my-3 gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">2 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 30%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">231</h5>
                                                </div>
                                                <div class="d-flex align-items-center mt-3 gap-3">
                                                    <h5 class="mb-0 flex-shrink-0">1 star :</h5>
                                                    <div
                                                        class="progress flex-grow-1"
                                                        role="progressbar"
                                                        aria-label="Basic example"
                                                        aria-valuenow="0"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        style="height: 10px"
                                                    >
                                                        <div class="progress-bar bg-warning rounded" style="width: 20%"></div>
                                                    </div>
                                                    <h5 class="mb-0 flex-shrink-0">149</h5>
                                                </div>
                                            </b-col>
                                        </div>
                                        <b-card-title tag="h4" class="">Shop Product Rating :</b-card-title>
                                        <div class="row g-2 mt-3 text-center">
                                            <b-col lg="4">
                                                <img :src="product3" alt="" class="img-fluid rounded border" />
                                                <div class="d-flex align-items-center justify-content-center mb-1 mt-2 gap-2">
                                                    <ul class="d-flex text-warning fs-18 list-unstyled m-0">
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-half-filled"></i>
                                                        </li>
                                                    </ul>
                                                    <p class="fw-medium text-dark fs-14 mb-0">4.5 / 5</p>
                                                </div>
                                            </b-col>
                                            <b-col lg="4">
                                                <img :src="product9" alt="" class="img-fluid rounded border" />
                                                <div class="d-flex align-items-center justify-content-center mb-1 mt-2 gap-2">
                                                    <ul class="d-flex text-warning fs-18 list-unstyled m-0">
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-half-filled"></i>
                                                        </li>
                                                    </ul>
                                                    <p class="fw-medium text-dark fs-14 mb-0">4.2 / 5</p>
                                                </div>
                                            </b-col>
                                            <b-col lg="4">
                                                <img :src="product10" alt="" class="img-fluid rounded border" />
                                                <div class="d-flex align-items-center justify-content-center mb-1 mt-2 gap-2">
                                                    <ul class="d-flex text-warning fs-18 list-unstyled m-0">
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-half-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star"></i>
                                                        </li>
                                                    </ul>
                                                    <p class="fw-medium text-dark fs-14 mb-0">3.5 / 5</p>
                                                </div>
                                            </b-col>
                                            <b-col lg="4">
                                                <img :src="product11" alt="" class="img-fluid rounded border" />
                                                <div class="d-flex align-items-center justify-content-center mb-1 mt-2 gap-2">
                                                    <ul class="d-flex text-warning fs-18 list-unstyled m-0">
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                    </ul>
                                                    <p class="fw-medium text-dark fs-14 mb-0">5 / 5</p>
                                                </div>
                                            </b-col>
                                            <b-col lg="4">
                                                <img :src="product12" alt="" class="img-fluid rounded border" />
                                                <div class="d-flex align-items-center justify-content-center mb-1 mt-2 gap-2">
                                                    <ul class="d-flex text-warning fs-18 list-unstyled m-0">
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-filled"></i>
                                                        </li>
                                                        <li>
                                                            <i class="ti ti-star-half-filled"></i>
                                                        </li>
                                                    </ul>
                                                    <p class="fw-medium text-dark fs-14 mb-0">4.4 / 5</p>
                                                </div>
                                            </b-col>
                                        </div>
                                    </div>
                                </div>
                            </b-row>
                        </b-tab>
                        <b-tab title-item-class="p-2 pb-0">
                            <template v-slot:title>
                                <span class="d-block d-sm-none"><Icon icon="solar:ticket-sale-bold" class="fs-20" /></span>
                                <span class="d-none d-sm-block"
                                    ><Icon icon="solar:ticket-sale-bold" class="fs-14 me-1 align-middle" /> Offers & Warranty</span
                                >
                            </template>

                            <b-card-title tag="h4" class="">Available Offers :</b-card-title>
                            <div class="row g-2 mt-3">
                                <b-col lg="6">
                                    <div class="rounded border p-2 text-center">
                                        <p class="fw-semibold text-dark fs-20 my-1">
                                            To Get This Price , Use Code : <span class="text-primary fw-semibold">WINTER2024</span>
                                        </p>
                                        <p class="fs-15 text-muted fw-medium">Fast & Free Delivery</p>
                                        <a href="#!" class="btn btn-primary">Show Offers</a>
                                    </div>
                                </b-col>
                                <b-col lg="6">
                                    <div class="rounded border p-2 text-center">
                                        <p class="fw-semibold text-dark fs-20 my-1">
                                            Enjoy an Additional 20% Discount on Your Purchase : <span class="text-primary fw-semibold">OCEN24</span>
                                        </p>
                                        <p class="fs-15 text-muted fw-medium">Fast & Free Delivery</p>
                                        <a href="#!" class="btn btn-primary">Show Offers</a>
                                    </div>
                                </b-col>
                            </div>
                            <b-card-title tag="h4" class="my-3">Shop Offers :</b-card-title>
                            <b-row class="g-3">
                                <b-col lg="3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <Icon icon="solar:refresh-square-bold" class="fs-28 text-primary" />
                                        </div>

                                        <div>
                                            <p class="text-dark fw-medium fs-16 mb-0">Free Return</p>
                                        </div>
                                    </div>
                                </b-col>
                                <b-col lg="3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <Icon icon="solar:money-bag-bold" class="fs-28 text-primary" />
                                        </div>

                                        <div>
                                            <p class="text-dark fw-medium fs-16 mb-0">Multiple Payment Option</p>
                                        </div>
                                    </div>
                                </b-col>
                                <b-col lg="3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <Icon icon="solar:sofa-2-bold-duotone" class="fs-28 text-primary" />
                                        </div>

                                        <div>
                                            <p class="text-dark fw-medium fs-16 mb-0">Free Assembly</p>
                                        </div>
                                    </div>
                                </b-col>
                                <b-col lg="3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <Icon icon="solar:verified-check-bold" class="fs-28 text-primary" />
                                        </div>

                                        <div>
                                            <p class="text-dark fw-medium fs-16 mb-0">Guaranteed Product Replace</p>
                                        </div>
                                    </div>
                                </b-col>
                                <b-col lg="3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <Icon icon="solar:tram-bold" class="fs-28 text-primary" />
                                        </div>

                                        <div>
                                            <p class="text-dark fw-medium fs-16 mb-0">Fast & Free Shipping</p>
                                        </div>
                                    </div>
                                </b-col>
                                <b-col lg="3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <Icon icon="solar:tag-price-bold" class="fs-28 text-primary" />
                                        </div>

                                        <div>
                                            <p class="text-dark fw-medium fs-16 mb-0">Your Best Price Matching</p>
                                        </div>
                                    </div>
                                </b-col>
                                <b-col lg="3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <Icon icon="solar:headphones-round-bold" class="fs-28 text-primary" />
                                        </div>

                                        <div>
                                            <p class="text-dark fw-medium fs-16 mb-0">24/7 Support</p>
                                        </div>
                                    </div>
                                </b-col>
                                <b-col lg="3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <Icon icon="solar:card-2-bold" class="fs-28 text-primary" />
                                        </div>

                                        <div>
                                            <p class="text-dark fw-medium fs-16 mb-0">Secure Checkout</p>
                                        </div>
                                    </div>
                                </b-col>
                            </b-row>
                            <div class="alert alert-success d-flex align-items-center mb-0 mt-4" role="alert">
                                <Icon icon="solar:shield-check-bold" class="fs-20 me-1" />
                                <div class="lh-1"><strong>3 Years - </strong>Guaranteed Product Global Warranty</div>
                            </div>
                        </b-tab>
                    </b-tabs>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { Icon } from '@iconify/vue';

import product10 from '@/images/products/p-10.png';
import product11 from '@/images/products/p-11.png';
import product12 from '@/images/products/p-12.png';
import product3 from '@/images/products/p-3.png';
import product9 from '@/images/products/p-9.png';

import { Autoplay, FreeMode, Navigation, Thumbs } from 'swiper/modules';
import type { Swiper as SwiperType } from 'swiper/types';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { ref } from 'vue';

const images = [product3, product9, product10, product11, product12];

const thumbsSwiper = ref<SwiperType | null>(null);

const setThumbsSwiper = (swiper: SwiperType) => {
    thumbsSwiper.value = swiper;
};
</script>
