<?php
/* =========================================================
   Demo "database" of users.
   NOTE: in a real app this would be a MySQL table.
   Passwords are stored as password_hash() output, never plain text.
   ========================================================= */

if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        // ----- Demo ADMIN (fixed credentials) -----
        'admin@eshopping.test' => [
            'id'        => 1,
            'name'      => 'Site Admin',
            'email'     => 'admin@eshopping.test',
            'password'  => password_hash('admin123', PASSWORD_DEFAULT),
            'role'      => 'admin',
            'status'    => 'approved',
            'created'   => date('Y-m-d H:i'),
        ],

        // ----- Demo CUSTOMER -----
        'customer@eshopping.test' => [
            'id'        => 2,
            'name'      => 'Jane Customer',
            'email'     => 'customer@eshopping.test',
            'password'  => password_hash('customer123', PASSWORD_DEFAULT),
            'role'      => 'customer',
            'status'    => 'approved',
            'created'   => date('Y-m-d H:i'),
        ],

        // ----- Demo SELLER (already approved) -----
        'seller@eshopping.test' => [
            'id'        => 3,
            'name'      => 'John Seller',
            'email'     => 'seller@eshopping.test',
            'password'  => password_hash('seller123', PASSWORD_DEFAULT),
            'role'      => 'seller',
            'status'    => 'approved',
            'created'   => date('Y-m-d H:i'),
        ],

        // ----- Demo SELLER (pending approval) -----
        'pending@eshopping.test' => [
            'id'        => 4,
            'name'      => 'Pat Pending',
            'email'     => 'pending@eshopping.test',
            'password'  => password_hash('pending123', PASSWORD_DEFAULT),
            'role'      => 'seller',
            'status'    => 'pending',
            'created'   => date('Y-m-d H:i'),
        ],
    ];
}