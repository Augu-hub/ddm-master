<template>
    <VerticalLayout>
        <PageTitle title="Basic Tables" subtitle="Tables" />
        <b-row>
            <b-col xl="6">
                <UICard title="My Table">
                    <b-table-simple responsive class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>Name</b-th>
                                <b-th>Phone Number</b-th>
                                <b-th>Date of Birth</b-th>
                                <b-th>Active?</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>Risa D. Pearson</b-td>
                                <b-td>336-508-2157</b-td>
                                <b-td>July 24, 1950</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch01" checked data-switch="success" />
                                        <label for="switch01" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Ann C. Thompson</b-td>
                                <b-td>646-473-2057</b-td>
                                <b-td>January 25, 1959</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch02" checked data-switch="success" />
                                        <label for="switch02" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Paul J. Friend</b-td>
                                <b-td>281-308-0793</b-td>
                                <b-td>September 1, 1939</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch03" data-switch="success" />
                                        <label for="switch03" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Linda G. Smith</b-td>
                                <b-td>606-253-1207</b-td>
                                <b-td>May 3, 1962</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch04" data-switch="success" />
                                        <label for="switch04" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Inverse Table">
                    <b-table-simple responsive dark class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>Name</b-th>
                                <b-th>Phone Number</b-th>
                                <b-th>Date of Birth</b-th>
                                <b-th>Active?</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>Risa D. Pearson</b-td>
                                <b-td>336-508-2157</b-td>
                                <b-td>July 24, 1950</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch6" data-switch="success" />
                                        <label for="switch6" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Ann C. Thompson</b-td>
                                <b-td>646-473-2057</b-td>
                                <b-td>January 25, 1959</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch7" checked data-switch="success" />
                                        <label for="switch7" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Paul J. Friend</b-td>
                                <b-td>281-308-0793</b-td>
                                <b-td>September 1, 1939</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch8" data-switch="success" />
                                        <label for="switch8" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Sean C. Nguyen</b-td>
                                <b-td>269-714-6825</b-td>
                                <b-td>February 5, 1994</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch10" checked data-switch="success" />
                                        <label for="switch10" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Striped Rows">
                    <b-table-simple responsive striped class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>User</b-th>
                                <b-th>Account No.</b-th>
                                <b-th>Balance</b-th>
                                <b-th>Action</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-2.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Risa D. Pearson
                                </b-td>
                                <b-td>AC336 508 2157</b-td>
                                <b-td>July 24, 1950</b-td>
                                <b-td class="text-muted">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-pencil"></i></a>
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-3.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Ann C. Thompson
                                </b-td>
                                <b-td>SB646 473 2057</b-td>
                                <b-td>January 25, 1959</b-td>
                                <b-td class="text-muted">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-pencil"></i></a>
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-4.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Paul J. Friend
                                </b-td>
                                <b-td>DL281 308 0793</b-td>
                                <b-td>September 1, 1939</b-td>
                                <b-td class="text-muted">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-pencil"></i></a>
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-5.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Sean C. Nguyen
                                </b-td>
                                <b-td>CA269 714 6825</b-td>
                                <b-td>February 5, 1994</b-td>
                                <b-td class="text-muted">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-pencil"></i></a>
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Striped Columns">
                    <div class="table-responsive-sm mb-0">
                        <table class="table-striped-columns mb-0 table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Account No.</th>
                                    <th>Balance</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <img src="@/images/users/avatar-2.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                        Risa D. Pearson
                                    </td>
                                    <td>AC336 508 2157</td>
                                    <td>July 24, 1950</td>
                                    <td class="text-muted text-center">
                                        <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-pencil"></i></a>
                                        <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <img src="@/images/users/avatar-3.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                        Ann C. Thompson
                                    </td>
                                    <td>SB646 473 2057</td>
                                    <td>January 25, 1959</td>
                                    <td class="text-muted text-center">
                                        <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-pencil"></i></a>
                                        <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <img src="@/images/users/avatar-4.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                        Paul J. Friend
                                    </td>
                                    <td>DL281 308 0793</td>
                                    <td>September 1, 1939</td>
                                    <td class="text-muted text-center">
                                        <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-pencil"></i></a>
                                        <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <img src="@/images/users/avatar-5.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                        Sean C. Nguyen
                                    </td>
                                    <td>CA269 714 6825</td>
                                    <td>February 5, 1994</td>
                                    <td class="text-muted text-center">
                                        <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-pencil"></i></a>
                                        <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Table Head Options">
                    <b-table-simple responsive class="mb-0">
                        <b-thead class="table-light">
                            <b-tr>
                                <b-th>Product</b-th>
                                <b-th>Courier</b-th>
                                <b-th>Process</b-th>
                                <b-th>Status</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>ASOS Ridley High Waist</b-td>
                                <b-td>FedEx</b-td>
                                <b-td>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-success"
                                            role="progressbar"
                                            style="width: 100%"
                                            aria-valuenow="100"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </b-td>
                                <b-td><i class="ti ti-circle text-success"></i> Delivered</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Marco Lightweight Shirt</b-td>
                                <b-td>DHL</b-td>
                                <b-td>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-warning"
                                            role="progressbar"
                                            style="width: 50%"
                                            aria-valuenow="50"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </b-td>
                                <b-td><i class="ti ti-circle text-warning"></i> Shipped</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Half Sleeve Shirt</b-td>
                                <b-td>Bright</b-td>
                                <b-td>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-info"
                                            role="progressbar"
                                            style="width: 25%"
                                            aria-valuenow="25"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </b-td>
                                <b-td><i class="ti ti-circle text-info"></i> Order Received</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Lightweight Jacket</b-td>
                                <b-td>FedEx</b-td>
                                <b-td>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-success"
                                            role="progressbar"
                                            style="width: 100%"
                                            aria-valuenow="100"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </b-td>
                                <b-td><i class="ti ti-circle text-success"></i> Delivered</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Table Head Options">
                    <b-table-simple responsive class="mb-0">
                        <b-thead class="table-dark">
                            <b-tr>
                                <b-th>Product</b-th>
                                <b-th>Courier</b-th>
                                <b-th>Process</b-th>
                                <b-th>Status</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>ASOS Ridley High Waist</b-td>
                                <b-td>FedEx</b-td>
                                <b-td>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-success"
                                            role="progressbar"
                                            style="width: 100%"
                                            aria-valuenow="100"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </b-td>
                                <b-td><i class="ti ti-circle text-success"></i> Delivered</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Marco Lightweight Shirt</b-td>
                                <b-td>DHL</b-td>
                                <b-td>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-warning"
                                            role="progressbar"
                                            style="width: 50%"
                                            aria-valuenow="50"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </b-td>
                                <b-td><i class="ti ti-circle text-warning"></i> Shipped</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Half Sleeve Shirt</b-td>
                                <b-td>Bright</b-td>
                                <b-td>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-info"
                                            role="progressbar"
                                            style="width: 25%"
                                            aria-valuenow="25"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </b-td>
                                <b-td><i class="ti ti-circle text-info"></i> Order Received</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Lightweight Jacket</b-td>
                                <b-td>FedEx</b-td>
                                <b-td>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar bg-success"
                                            role="progressbar"
                                            style="width: 100%"
                                            aria-valuenow="100"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </b-td>
                                <b-td><i class="ti ti-circle text-success"></i> Delivered</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Hoverable Rows">
                    <b-table-simple responsive hover class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>Product</b-th>
                                <b-th>Price</b-th>
                                <b-th>Quantity</b-th>
                                <b-th>Amount</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>ASOS Ridley High Waist</b-td>
                                <b-td>$79.49</b-td>
                                <b-td><span class="badge bg-primary">82 Pcs</span></b-td>
                                <b-td>$6,518.18</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Marco Lightweight Shirt</b-td>
                                <b-td>$128.50</b-td>
                                <b-td><span class="badge bg-primary">37 Pcs</span></b-td>
                                <b-td>$4,754.50</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Half Sleeve Shirt</b-td>
                                <b-td>$39.99</b-td>
                                <b-td><span class="badge bg-primary">64 Pcs</span></b-td>
                                <b-td>$2,559.36</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Lightweight Jacket</b-td>
                                <b-td>$20.00</b-td>
                                <b-td><span class="badge bg-primary">184 Pcs</span></b-td>
                                <b-td>$3,680.00</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Inverse Hoverable Rows">
                    <b-table-simple responsive hover dark class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>Product</b-th>
                                <b-th>Price</b-th>
                                <b-th>Quantity</b-th>
                                <b-th>Amount</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>ASOS Ridley High Waist</b-td>
                                <b-td>$79.49</b-td>
                                <b-td><span class="badge bg-primary">82 Pcs</span></b-td>
                                <b-td>$6,518.18</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Marco Lightweight Shirt</b-td>
                                <b-td>$128.50</b-td>
                                <b-td><span class="badge bg-primary">37 Pcs</span></b-td>
                                <b-td>$4,754.50</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Half Sleeve Shirt</b-td>
                                <b-td>$39.99</b-td>
                                <b-td><span class="badge bg-primary">64 Pcs</span></b-td>
                                <b-td>$2,559.36</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Lightweight Jacket</b-td>
                                <b-td>$20.00</b-td>
                                <b-td><span class="badge bg-primary">184 Pcs</span></b-td>
                                <b-td>$3,680.00</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Bordered Table">
                    <b-table-simple responsive bordered class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>User</b-th>
                                <b-th>Account No.</b-th>
                                <b-th>Balance</b-th>
                                <b-th class="text-center">Action</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-6.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Risa D. Pearson
                                </b-td>
                                <b-td>AC336 508 2157</b-td>
                                <b-td>July 24, 1950</b-td>
                                <b-td class="text-muted text-center">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-7.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Ann C. Thompson
                                </b-td>
                                <b-td>SB646 473 2057</b-td>
                                <b-td>January 25, 1959</b-td>
                                <b-td class="text-muted text-center">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-8.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Paul J. Friend
                                </b-td>
                                <b-td>DL281 308 0793</b-td>
                                <b-td>September 1, 1939</b-td>
                                <b-td class="text-muted text-center">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-9.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Sean C. Nguyen
                                </b-td>
                                <b-td>CA269 714 6825</b-td>
                                <b-td>February 5, 1994</b-td>
                                <b-td class="text-muted text-center">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Bordered Color Table">
                    <b-table-simple responsive bordered class="border-secondary mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>User</b-th>
                                <b-th>Account No.</b-th>
                                <b-th>Balance</b-th>
                                <b-th class="text-center">Action</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-6.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Risa D. Pearson
                                </b-td>
                                <b-td>AC336 508 2157</b-td>
                                <b-td>July 24, 1950</b-td>
                                <b-td class="text-muted text-center">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-7.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Ann C. Thompson
                                </b-td>
                                <b-td>SB646 473 2057</b-td>
                                <b-td>January 25, 1959</b-td>
                                <b-td class="text-muted text-center">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-8.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Paul J. Friend
                                </b-td>
                                <b-td>DL281 308 0793</b-td>
                                <b-td>September 1, 1939</b-td>
                                <b-td class="text-muted text-center">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>
                                    <img src="@/images/users/avatar-9.jpg" alt="table-user" class="avatar-sm rounded-circle me-2" />
                                    Sean C. Nguyen
                                </b-td>
                                <b-td>CA269 714 6825</b-td>
                                <b-td>February 5, 1994</b-td>
                                <b-td class="text-muted text-center">
                                    <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                </b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Always Responsive">
                    <b-table-simple responsive class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th scope="col">#</b-th>
                                <b-th scope="col">Heading</b-th>
                                <b-th scope="col">Heading</b-th>
                                <b-th scope="col">Heading</b-th>
                                <b-th scope="col">Heading</b-th>
                                <b-th scope="col">Heading</b-th>
                                <b-th scope="col">Heading</b-th>
                                <b-th scope="col">Heading</b-th>
                                <b-th scope="col">Heading</b-th>
                                <b-th scope="col">Heading</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-th scope="row">1</b-th>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                            </b-tr>
                            <b-tr>
                                <b-th scope="row">2</b-th>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                            </b-tr>
                            <b-tr>
                                <b-th scope="row">3</b-th>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                                <b-td>Cell</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Basic Borderless Example">
                    <b-table-simple responsive borderless class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>Name</b-th>
                                <b-th>Phone Number</b-th>
                                <b-th>Date of Birth</b-th>
                                <b-th>Active?</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>Risa D. Pearson</b-td>
                                <b-td>336-508-2157</b-td>
                                <b-td>July 24, 1950</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch21" checked data-switch="success" />
                                        <label for="switch21" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Ann C. Thompson</b-td>
                                <b-td>646-473-2057</b-td>
                                <b-td>January 25, 1959</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch22" checked data-switch="success" />
                                        <label for="switch22" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Paul J. Friend</b-td>
                                <b-td>281-308-0793</b-td>
                                <b-td>September 1, 1939</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch23" data-switch="success" />
                                        <label for="switch23" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Linda G. Smith</b-td>
                                <b-td>606-253-1207</b-td>
                                <b-td>May 3, 1962</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch24" data-switch="success" />
                                        <label for="switch24" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Inverse Borderless Table">
                    <b-table-simple responsive borderless dark class="mb-0">
                        <b-thead>
                            <b-tr>
                                <th>Name</th>
                                <th>Phone Number</th>
                                <th>Date of Birth</th>
                                <th>Active?</th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <td>Risa D. Pearson</td>
                                <td>336-508-2157</td>
                                <td>July 24, 1950</td>
                                <td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch25" data-switch="success" />
                                        <label for="switch25" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </td>
                            </b-tr>
                            <b-tr>
                                <td>Ann C. Thompson</td>
                                <td>646-473-2057</td>
                                <td>January 25, 1959</td>
                                <td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch26" checked data-switch="success" />
                                        <label for="switch26" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </td>
                            </b-tr>
                            <b-tr>
                                <td>Paul J. Friend</td>
                                <td>281-308-0793</td>
                                <td>September 1, 1939</td>
                                <td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch27" data-switch="success" />
                                        <label for="switch27" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </td>
                            </b-tr>
                            <b-tr>
                                <td>Sean C. Nguyen</td>
                                <td>269-714-6825</td>
                                <td>February 5, 1994</td>
                                <td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch28" checked data-switch="success" />
                                        <label for="switch28" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Active Tables">
                    <b-table-simple responsive class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>Name</b-th>
                                <b-th>Phone Number</b-th>
                                <b-th>Date of Birth</b-th>
                                <b-th>Active?</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr class="table-active">
                                <b-td>Risa D. Pearson</b-td>
                                <b-td>336-508-2157</b-td>
                                <b-td>July 24, 1950</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch29" checked data-switch="success" />
                                        <label for="switch29" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Ann C. Thompson</b-td>
                                <b-td>646-473-2057</b-td>
                                <b-td>January 25, 1959</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch30" checked data-switch="success" />
                                        <label for="switch30" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Paul J. Friend</b-td>
                                <b-td>281-308-0793</b-td>
                                <b-td>September 1, 1939</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch31" data-switch="success" />
                                        <label for="switch31" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td scope="row">Linda G. Smith</b-td>
                                <b-td colspan="2" class="table-active">606-253-1207</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch32" data-switch="success" />
                                        <label for="switch32" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Paul J. Friend</b-td>
                                <b-td>281-308-0793</b-td>
                                <b-td>September 1, 1939</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch33" checked data-switch="success" />
                                        <label for="switch33" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Nesting">
                    <b-table-simple responsive striped class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>Name</b-th>
                                <b-th>Phone Number</b-th>
                                <b-th>Date of Birth</b-th>
                                <b-th>Active?</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>Risa D. Pearson</b-td>
                                <b-td>336-508-2157</b-td>
                                <b-td>July 24, 1950</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch34" checked data-switch="success" />
                                        <label for="switch34" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td colspan="4">
                                    <table class="mb-0 table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Phone Number</th>
                                                <th>Date of Birth</th>
                                                <th>Active?</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <b-td>Risa D. Pearson</b-td>
                                                <b-td>336-508-2157</b-td>
                                                <b-td>July 24, 1950</b-td>
                                                <b-td>
                                                    <!-- Switch-->
                                                    <div>
                                                        <input type="checkbox" id="switch35" checked data-switch="success" />
                                                        <label for="switch35" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                                    </div>
                                                </b-td>
                                            </tr>
                                            <tr>
                                                <b-td>Ann C. Thompson</b-td>
                                                <b-td>646-473-2057</b-td>
                                                <b-td>January 25, 1959</b-td>
                                                <b-td>
                                                    <!-- Switch-->
                                                    <div>
                                                        <input type="checkbox" id="switch36" data-switch="success" />
                                                        <label for="switch36" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                                    </div>
                                                </b-td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Linda G. Smith</b-td>
                                <b-td>606-253-1207</b-td>
                                <b-td>September 2, 1939</b-td>
                                <b-td>
                                    <!-- Switch-->
                                    <div>
                                        <input type="checkbox" id="switch37" data-switch="success" />
                                        <label for="switch37" data-on-label="Yes" data-off-label="No" class="d-block mb-0"></label>
                                    </div>
                                </b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Small Table">
                    <b-table-simple responsive small class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>Product</b-th>
                                <b-th>Price</b-th>
                                <b-th>Quantity</b-th>
                                <b-th>Amount</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>ASOS Ridley High Waist</b-td>
                                <b-td>$79.49</b-td>
                                <b-td><span class="badge bg-primary">82 Pcs</span></b-td>
                                <b-td>$6,518.18</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Marco Lightweight Shirt</b-td>
                                <b-td>$128.50</b-td>
                                <b-td><span class="badge bg-primary">37 Pcs</span></b-td>
                                <b-td>$4,754.50</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Half Sleeve Shirt</b-td>
                                <b-td>$39.99</b-td>
                                <b-td><span class="badge bg-primary">64 Pcs</span></b-td>
                                <b-td>$2,559.36</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Lightweight Jacket</b-td>
                                <b-td>$20.00</b-td>
                                <b-td><span class="badge bg-primary">184 Pcs</span></b-td>
                                <b-td>$3,680.00</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Marco Shoes</b-td>
                                <b-td>$28.49</b-td>
                                <b-td><span class="badge bg-primary">69 Pcs</span></b-td>
                                <b-td>$1,965.81</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>ASOS Ridley High Waist</b-td>
                                <b-td>$79.49</b-td>
                                <b-td><span class="badge bg-primary">82 Pcs</span></b-td>
                                <b-td>$6,518.18</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Table Foot">
                    <b-table-simple responsive class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th>Product</b-th>
                                <b-th>Price</b-th>
                                <b-th>Quantity</b-th>
                                <b-th>Amount</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>ASOS Ridley High Waist</b-td>
                                <b-td>$79.49</b-td>
                                <b-td><span class="badge bg-primary">82 Pcs</span></b-td>
                                <b-td>$6,518.18</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Marco Lightweight Shirt</b-td>
                                <b-td>$128.50</b-td>
                                <b-td><span class="badge bg-primary">37 Pcs</span></b-td>
                                <b-td>$4,754.50</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Half Sleeve Shirt</b-td>
                                <b-td>$39.99</b-td>
                                <b-td><span class="badge bg-primary">64 Pcs</span></b-td>
                                <b-td>$2,559.36</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Lightweight Jacket</b-td>
                                <b-td>$20.00</b-td>
                                <b-td><span class="badge bg-primary">184 Pcs</span></b-td>
                                <b-td>$3,680.00</b-td>
                            </b-tr>
                        </b-tbody>
                        <b-tfoot>
                            <b-tr>
                                <b-th>Footer</b-th>
                                <b-td>Footer</b-td>
                                <b-td>Footer</b-td>
                                <b-td>Footer</b-td>
                            </b-tr>
                        </b-tfoot>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Captions">
                    <b-table-simple responsive class="mb-0">
                        <caption>
                            List of users
                        </caption>
                        <b-thead>
                            <b-tr>
                                <b-th>Product</b-th>
                                <b-th>Price</b-th>
                                <b-th>Quantity</b-th>
                                <b-th>Amount</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>ASOS Ridley High Waist</b-td>
                                <b-td>$79.49</b-td>
                                <b-td><span class="badge bg-primary">82 Pcs</span></b-td>
                                <b-td>$6,518.18</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Marco Lightweight Shirt</b-td>
                                <b-td>$128.50</b-td>
                                <b-td><span class="badge bg-primary">37 Pcs</span></b-td>
                                <b-td>$4,754.50</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Half Sleeve Shirt</b-td>
                                <b-td>$39.99</b-td>
                                <b-td><span class="badge bg-primary">64 Pcs</span></b-td>
                                <b-td>$2,559.36</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Lightweight Jacket</b-td>
                                <b-td>$20.00</b-td>
                                <b-td><span class="badge bg-primary">184 Pcs</span></b-td>
                                <b-td>$3,680.00</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Caption Top">
                    <b-table-simple responsive class="mb-0 caption-top">
                        <caption>
                            List of users
                        </caption>
                        <b-thead>
                            <b-tr>
                                <b-th>Product</b-th>
                                <b-th>Price</b-th>
                                <b-th>Quantity</b-th>
                                <b-th>Amount</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-td>ASOS Ridley High Waist</b-td>
                                <b-td>$79.49</b-td>
                                <b-td><span class="badge bg-primary">82 Pcs</span></b-td>
                                <b-td>$6,518.18</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Marco Lightweight Shirt</b-td>
                                <b-td>$128.50</b-td>
                                <b-td><span class="badge bg-primary">37 Pcs</span></b-td>
                                <b-td>$4,754.50</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Half Sleeve Shirt</b-td>
                                <b-td>$39.99</b-td>
                                <b-td><span class="badge bg-primary">64 Pcs</span></b-td>
                                <b-td>$2,559.36</b-td>
                            </b-tr>
                            <b-tr>
                                <b-td>Lightweight Jacket</b-td>
                                <b-td>$20.00</b-td>
                                <b-td><span class="badge bg-primary">184 Pcs</span></b-td>
                                <b-td>$3,680.00</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
</script>
