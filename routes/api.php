<?php

Route::group(['prefix' => 'v1', 'namespace' => 'Api'], function () {

    Route::group(['middleware' => 'jwt.auth'], function () {

    });
    Route::get('verify/token', 'TokenController@verify');
    Route::post('login', 'AuthController@login');

    Route::group(['middleware' => 'api_token'], function () {
    //project
    Route::group(['prefix' => 'projects'], function () {
        Route::post('/', 'ProjectController@create');
        Route::post('/{id}', 'ProjectController@update');
        Route::get('/', 'ProjectController@list');
        Route::get('/{id}', 'ProjectController@getById');
        Route::get('/delete/{id}', 'ProjectController@delete');
        // detail
        Route::get('/detail/{slug}', 'ProjectController@getBySlug');
        //type
        Route::post('/type/', 'ProjectTypeController@create');
        Route::post('/type/{id}', 'ProjectTypeController@update');
        Route::get('/type', 'ProjectTypeController@list');
        Route::get('/type/{id}', 'ProjectTypeController@getById');
        Route::get('/type/delete', 'ProjectTypeController@delete');
        //category
        Route::post('/category/', 'ProjectCategoryController@create');
        Route::post('/category/{id}', 'ProjectCategoryController@update');
        Route::get('/category', 'ProjectCategoryController@list');
        Route::get('/category/{id}', 'ProjectCategoryController@getById');
        Route::get('/category/delete', 'ProjectCategoryController@delete');
        //quantity
        Route::post('/quantity/', 'ProjectQuantityController@create');
        Route::post('/quantity/{id}', 'ProjectQuantityController@update');
        Route::get('/quantity', 'ProjectQuantityController@list');
        Route::get('/quantity/{id}', 'ProjectQuantityController@getById');
        Route::get('/quantity/delete', 'ProjectQuantityController@delete');
        //service
        Route::post('/service/', 'ProjectServiceController@create');
        Route::post('/service/{id}', 'ProjectServiceController@update');
        Route::get('/service', 'ProjectServiceController@list');
        Route::get('/service/{id}', 'ProjectServiceController@getById');
        Route::get('/service/delete', 'ProjectServiceController@delete');
    });

    Route::get('view-count', 'CommonController@viewCount');
    Route::get('mail', 'MailController@send');

    Route::post('register', 'AuthController@register');
    Route::post('social/facebook_login', 'AuthController@facebookLogin');
    Route::get('activation/{code}', 'AuthController@active');
    Route::get('redirect/{service}', 'AuthController@redirect');


    Route::post('token/{token?}', 'TokenController@getToken');
    Route::get('token/{token}/getUser', 'TokenController@getUserByToken');
    Route::get('forgot/{email}', 'AuthController@forgot');
    Route::post('resetPass', 'AuthController@resetPassword');
    Route::group(['middleware' => ['web']], function () {
        Route::get('callback/{service}', 'AuthController@callback');
    });

    //collections
    Route::group(['prefix' => 'collections'], function () {
        Route::get('/products/{slug}', 'CollectionController@getBySlug');
        Route::post('/multiple/', 'CollectionController@updateMultiple');
        Route::post('/publish', 'CollectionController@publish');
        Route::post('/available', 'CollectionController@available');
        Route::get('', 'CollectionController@list');
        Route::get('list', 'CollectionController@listCollection');
        Route::post('', 'CollectionController@store');
        Route::put('/{collection_id}', 'CollectionController@update');
        Route::post('/{collection_id}', 'CollectionController@update');
        Route::get('/delete', 'CollectionController@delete');
        Route::get('/children', 'CollectionController@getChildren');
        Route::get('/detail/{slug}', 'CollectionController@getBySlug');
        Route::get('/specs/{collection_id}', 'CollectionController@loadSpecs');
        Route::get('/search', 'CollectionController@search');
    });

    Route::group(['prefix' => 'review'], function () {
        Route::get('/show/{id}', 'ReviewController@show');
        Route::post('/review/{id}', 'ReviewController@review');
    });

    Route::group(['prefix' => 'account'], function () {
        Route::get('orders', 'AccountController@orders');
        Route::get('wishlist', 'AccountController@wishlist');
        Route::get('order', 'AccountController@order');
        Route::post('update', 'AccountController@update');
        Route::get('/address/{address}', 'AccountController@getAddress');
        Route::get('addresses', 'AccountController@addresses');
        Route::post('addresses', 'AccountController@addAddresses');
        Route::post('addresses/{id}/delete', 'AccountController@deleteAddresses');
        Route::post('addresses/{id}', 'AccountController@updateAddresses');
        Route::get('get-account', 'AccountController@findAccount');
        Route::post('account-update', 'AccountController@updateAccount');
        Route::get('active/{code}', 'AccountController@active');
        Route::get('password/{code}', 'AccountController@forgotPassword');

        Route::group(['prefix' => 'favorites'], function () {
            Route::get('/get_product_by_favorite', 'FavoriteController@getProductByFavorite');
            Route::post('store', 'FavoriteController@store');
            Route::get('delete/{id}', 'FavoriteController@delete');
        });
    });

    Route::group(['prefix' => 'products'], function () {
        Route::post('/multiple/', 'ProductController@updateMultiple');
        Route::get('', 'ProductController@list');
        Route::get('/items', 'ProductController@item');
        Route::post('', 'ProductController@store');
        Route::post('/publish', 'ProductController@publish');
        Route::post('/available', 'ProductController@available');
        Route::put('/{product_id}', 'ProductController@update');
        Route::post('/{product_id}', 'ProductController@update');
        Route::get('/delete', 'ProductController@delete');
        Route::get('/detail/{slug}', 'ProductController@getBySlug');
        Route::get('/breadcrumb', 'ProductController@getBreadcrumb');
        Route::get('/collection/{collection}', 'ProductController@getByCollection');
        Route::get('/search', 'ProductController@search');
        Route::get('/variants', 'ProductController@variants');
        Route::get('/home', 'ProductController@home');

    });

    Route::group(['prefix' => 'leads'], function () {
        Route::post('/multiple/', 'LeadController@updateMultiple');
        Route::get('', 'LeadController@list');
        Route::get('/{id}', 'LeadController@show');
        Route::post('', 'LeadController@store');
        Route::post('/update/{id}', 'LeadController@update');
        Route::post('/publish', 'LeadController@publish');
        Route::post('/available', 'LeadController@available');
        Route::put('/{product_id}', 'LeadController@update');
        Route::post('/{product_id}', 'LeadController@update');
        Route::get('/delete/{id}', 'LeadController@delete');
        Route::get('/detail/{slug}', 'LeadController@getBySlug');
        Route::get('/collection/{collection}', 'LeadController@getByCollection');
        Route::get('/search', 'LeadController@search');

    });

    Route::group(['prefix' => 'geos'], function () {
        Route::get('/get-by-code/{code}', 'GeoController@getByCode');
        Route::post('/get-district-by-code', 'GeoController@getMultipleByCode');
    });


    //vendors
    Route::group(['prefix' => 'vendors'], function () {
        Route::post('/multiple/', 'VendorController@updateMultiple');
        Route::post('/publish', 'VendorController@publish');
        Route::post('/available', 'VendorController@available');
        Route::get('', 'VendorController@list');
        Route::post('', 'VendorController@store');
        Route::put('/{vendor_id}', 'VendorController@update');
        Route::post('/{vendor_id}', 'VendorController@update');
        Route::get('/delete', 'VendorController@delete');
        Route::get('/detail/{slug}', 'VendorController@getBySlug');
        Route::get('/collection/{collection_id}', 'VendorController@getVendorByCollection');
        Route::get('/search', 'VendorController@search');
    });

    //pages
    Route::group(['prefix' => 'pages'], function () {
        Route::post('/multiple/', 'PageController@updateMultiple');
        Route::post('/publish', 'PageController@publish');
        Route::post('/available', 'PageController@available');
        Route::get('', 'PageController@list');
        Route::post('', 'PageController@store');
        Route::put('/{page_id}', 'PageController@update');
        Route::post('/{page_id}', 'PageController@update');
        Route::get('/delete', 'PageController@delete');
        Route::get('/children', 'PageController@getChildren');
        Route::get('/detail/{alias}', 'PageController@getByAlias');
        Route::get('/search', 'PageController@search');
    });

    //blog
    Route::group(['prefix' => 'blog'], function () {
        Route::post('/multiple/', 'BlogController@updateMultiple');
        Route::post('/publish', 'BlogController@publish');
        Route::post('/available', 'BlogController@available');
        Route::get('', 'BlogController@list');
        Route::post('', 'BlogController@store');
        Route::put('/{blog_id}', 'BlogController@update');
        Route::post('/{blog_id}', 'BlogController@update');
        Route::get('/delete', 'BlogController@delete');
        Route::get('/children', 'BlogController@getChildren');
        Route::get('/detail/{slug}', 'BlogController@getBySlug');
        Route::get('/search', 'BlogController@search');
        Route::get('/index', 'BlogController@getBlog');
        Route::get('/page', 'BlogController@getPage');
        Route::get('/home', 'BlogController@getHome');
    });


    //navigation
    Route::group(['prefix' => 'navigation'], function () {
        Route::post('/multiple/', 'NavigationController@updateMultiple');
        Route::get('', 'NavigationController@list');
        Route::post('', 'NavigationController@store');
        Route::put('/{navigation_id}', 'NavigationController@update');
        Route::post('/{navigation_id}', 'NavigationController@update');
        Route::get('/delete', 'NavigationController@delete');
        Route::get('/children', 'NavigationController@getChildren');
        Route::get('/detail/{slug}', 'NavigationController@getBySlug');
        Route::get('/menu', 'NavigationController@getMenu');
        Route::get('/default-menu', 'NavigationController@getTopMenu');
        Route::get('/search', 'NavigationController@search');

    });

    //articles
    Route::group(['prefix' => 'services'], function () {
        Route::post('/multiple/', 'ServiceController@updateMultiple');
        Route::get('', 'ServiceController@list');
        Route::post('', 'ServiceController@store');
        Route::put('/{service_id}', 'ServiceController@update');
        Route::post('/{service_id}', 'ServiceController@update');
        Route::get('/delete', 'ServiceController@delete');
        Route::get('/children', 'ServiceController@getChildren');
        Route::get('/detail/{slug}', 'ServiceController@getBySlug');
        Route::get('/search', 'ServiceController@search');
    });

    //articles
    Route::group(['prefix' => 'articles'], function () {
        Route::post('/multiple/', 'ArticleController@updateMultiple');
        Route::post('/publish', 'ArticleController@publish');
        Route::post('/available', 'ArticleController@available');
        Route::get('', 'ArticleController@list');
        Route::post('', 'ArticleController@store');
        Route::put('/{article_id}', 'ArticleController@update');
        Route::post('/{article_id}', 'ArticleController@update');
        Route::get('/delete', 'ArticleController@delete');
        Route::get('/children', 'ArticleController@getChildren');
        Route::get('/detail/{slug}', 'ArticleController@getBySlug');
        Route::get('/search', 'ArticleController@search');
    });

    //staff
    Route::group(['prefix' => 'staffs'], function () {
        Route::post('/multiple/', 'StaffController@updateMultiple');
        Route::get('', 'StaffController@list');
        Route::post('', 'StaffController@store');
        Route::post('/login', 'StaffController@login');
        Route::put('/{staff_id}', 'StaffController@update');
        Route::post('/{staff_id}', 'StaffController@update');
        Route::get('/delete', 'StaffController@delete');
        Route::get('/search', 'StaffController@search');
        Route::get('/profile', 'StaffController@profile');

    });

    //customer
    Route::group(['prefix' => 'customers'], function () {
        Route::post('/multiple/', 'CustomerController@updateMultiple');
        Route::get('', 'CustomerController@list');
        Route::post('', 'CustomerController@store');
        Route::put('/{customer_id}', 'CustomerController@update');
        Route::post('/{customer_id}', 'CustomerController@update');
        Route::get('/delete/{id}', 'CustomerController@delete');
        Route::get('/search', 'CustomerController@search');
    });

    //discount
    Route::group(['prefix' => 'discounts'], function () {
        Route::post('/multiple-coupon', 'DiscountController@addMultiple');
        Route::post('/multiple/', 'DiscountController@updateMultiple');
        Route::post('/publish', 'DiscountController@publish');
        Route::post('/available', 'DiscountController@available');
        Route::get('', 'DiscountController@list');
        Route::post('', 'DiscountController@store');
        Route::put('/{discount_id}', 'DiscountController@update');
        Route::post('/{discount_id}', 'DiscountController@update');
        Route::get('/delete', 'DiscountController@delete');
        Route::get('/search', 'DiscountController@search');
    });

    //carts
    Route::group(['prefix' => 'carts'], function () {
        Route::post('/discount', 'CartController@discount');
        Route::get('/delete', 'CartController@deleteBoxItem');
        Route::get('/{device_token}/{data?}', 'CartController@show');
        Route::post('/delete', 'CartController@delete');
        Route::post('/update', 'CartController@update');
        Route::post('/shipping', 'CartController@shipping');
        Route::post('/surcharge', 'CartController@surcharge');
        Route::post('/', 'CartController@store');

    });

    //box
    Route::group(['prefix' => 'box'], function () {
        Route::get('', 'BoxController@index');
        Route::get('/{seo_alias}', 'BoxController@detail');
    });

    Route::group(['prefix' => 'boxes'], function () {
        Route::post('/add', 'BoxController@addBoxItem');
        Route::post('/delete', 'BoxController@deleteBoxItem');
        Route::post('/clear', 'BoxController@clearBox');
        Route::post('/cart', 'BoxController@addBoxToCart');
        Route::get('/', 'BoxController@getBox');
    });


    //order
    Route::group(['prefix' => 'orders'], function () {
        Route::post('/', 'OrderController@saveOrder');
        Route::get('/', 'OrderController@getOrder');
        Route::get('/update', 'OrderController@updateOrder');
        Route::get('/delete', 'OrderController@delete');
        Route::post('/cancel-status', 'OrderController@updateStatus');
        Route::post('/{order_id}', 'OrderController@update');
        Route::get('/add-transaction', 'OrderController@addTransaction');
        Route::get('/search', 'OrderController@search');
        Route::get('/copy/{order_id}', 'OrderController@copy');
    });

    //subscription
    Route::group(['prefix' => 'subscriptions'], function () {
        Route::post('/', 'SubscriptionController@store');
        Route::get('/', 'SubscriptionController@list');
        Route::get('/update', 'SubscriptionController@update');
        Route::post('/update', 'SubscriptionController@update');
        Route::get('/delete', 'SubscriptionController@delete');
        Route::post('/{order_id}', 'SubscriptionController@update');
        Route::post('/suggest-product/{order_id}', 'SubscriptionController@suggestProduct');
        Route::post('/boxes/{order_id}', 'SubscriptionController@updateBoxes');
        Route::get('/search', 'SubscriptionController@search');
    });

    //role
    Route::group(['prefix' => 'roles'], function () {
        Route::post('/multiple/', 'RoleController@updateMultiple');
        Route::get('', 'RoleController@list');
        Route::post('', 'RoleController@store');
        Route::put('/{staff_id}', 'RoleController@update');
        Route::post('/{staff_id}', 'RoleController@update');
        Route::get('/delete', 'RoleController@delete');
        Route::get('/search', 'RoleController@search');
        Route::get('/permissions', 'RoleController@getPermissions');
        Route::get('/checkPermission/{permission}', 'RoleController@checkPermission');
    });

    Route::group(['prefix' => 'tags'], function () {
        Route::post('/', 'TagController@store');
        Route::get('/', 'TagController@list');
        Route::get('/update', 'TagController@update');
        Route::post('/update', 'TagController@update');
        Route::get('/delete', 'TagController@delete');
        Route::post('/{order_id}', 'TagController@update');
        Route::get('/search', 'TagController@search');
    });

    Route::group(['prefix' => 'warehouse'], function () {
        Route::post('/', 'WarehouseController@store');
        Route::get('/', 'WarehouseController@list');
        Route::get('/update', 'WarehouseController@update');
        Route::post('/update', 'WarehouseController@update');
        Route::get('/delete', 'WarehouseController@delete');
        Route::post('/{order_id}', 'WarehouseController@update');
        Route::get('/search', 'WarehouseController@search');
    });

    Route::group(['prefix' => 'inventories'], function () {
        Route::post('/', 'InventoryController@store');
        Route::get('/', 'InventoryController@list');
        Route::get('/update', 'InventoryController@update');
        Route::post('/update', 'InventoryController@update');
        Route::get('/delete', 'InventoryController@delete');
        Route::post('/{order_id}', 'InventoryController@update');
        Route::get('/search', 'InventoryController@search');
    });

    //report
    Route::group(['prefix' => 'reports'], function () {
        Route::get('', 'ReportController@index');
        Route::get('/order', 'ReportController@orders');
    });

    //discount
    Route::group(['prefix' => 'settings'], function () {
        Route::post('/multiple/', 'SettingController@updateMultiple');
        Route::get('', 'SettingController@list');
        Route::post('', 'SettingController@store');
        Route::put('/{setting_id}', 'SettingController@update');
        Route::post('/{setting_id}', 'SettingController@update');
        Route::get('/delete', 'SettingController@delete');
        Route::get('/general', 'SettingController@general');
    });

    Route::get('/delete', 'SettingController@delete');

    Route::group(['prefix' => 'homepage'], function () {
        Route::get('', 'SettingController@homepage');
    });

    Route::group(['prefix' => 'search'], function () {
        Route::get('', 'SearchController@search');
    });

    //Items
    Route::group(['prefix' => 'items'], function () {
        Route::post('/multiple/', 'ItemController@updateMultiple');
        Route::get('', 'ItemController@list');
        Route::post('', 'ItemController@store');
        Route::put('/{product_id}', 'ItemController@update');
        Route::post('/{product_id}', 'ItemController@update');
        Route::get('/delete', 'ItemController@delete');
        Route::get('/detail/{slug}', 'ItemController@getBySlug');
        Route::get('/breadcrumb', 'ItemController@getBreadcrumb');
        Route::get('/collection/{collection}', 'ItemController@getByCollection');
        Route::get('/search', 'ItemController@search');

    });

    //Review
    Route::group(['prefix' => 'reviews'], function () {
        Route::get('', 'ReviewController@list');
        Route::post('', 'ReviewController@store');
        Route::post('/publish', 'ReviewController@publish');
        Route::post('/{review_id}', 'ReviewController@update');
        Route::get('/delete', 'ReviewController@delete');
        Route::get('/detail/{review_id}', 'ReviewController@getById');
        Route::get('/index', 'ReviewController@index');
        Route::get('/{product_id}', 'ReviewController@getByProductId');
    });

    //Unbox
    Route::group(['prefix' => 'unbox'], function () {
        Route::get('', 'UnBoxController@list');
        Route::post('', 'UnBoxController@store');
        Route::post('/{un_box_id}', 'UnBoxController@update');
        Route::get('/delete', 'UnBoxController@delete');
        Route::get('/detail/{un_box_id}', 'UnBoxController@getById');
    });

    Route::group(['prefix' => 'script'], function () {
        Route::get('', 'ScriptController@updateDiscountCount');
        Route::get('/shipping', 'ScriptController@updateShipping');
    });

    Route::group(['prefix' => 'variants'], function () {
        Route::post('/', 'VariantController@store');
        Route::get('/', 'VariantController@list');
        Route::get('/update', 'VariantController@update');
        Route::post('/update', 'VariantController@update');
        Route::get('/delete', 'VariantController@delete');
        Route::post('/{order_id}', 'VariantController@update');
        Route::get('/search', 'VariantController@search');
    });

    Route::group(['prefix' => 'slides'], function () {
        Route::post('/', 'SlideController@store');
        Route::get('/', 'SlideController@list');
        Route::get('/update', 'SlideController@update');
        Route::post('/update', 'SlideController@update');
        Route::get('/delete', 'SlideController@delete');
        Route::post('/{id}', 'SlideController@update');
        Route::get('/search', 'SlideController@search');
    });

    Route::group(['prefix' => 'testimonials'], function () {
        Route::post('/', 'TestimonialController@store');
        Route::get('/', 'TestimonialController@list');
        Route::get('/update', 'TestimonialController@update');
        Route::post('/update', 'TestimonialController@update');
        Route::get('/delete', 'TestimonialController@delete');
        Route::post('/{id}', 'TestimonialController@update');
    });

    Route::group(['prefix' => 'wishlist'], function () {
        Route::post('/', 'WishlistController@store');
        Route::get('/', 'WishlistController@list');
        Route::get('/update', 'WishlistController@update');
        Route::post('/update', 'WishlistController@update');
        Route::get('/delete', 'WishlistController@delete');
        Route::post('/{id}', 'WishlistController@update');
    });

    Route::group(['prefix' => 'gallery'], function () {
        Route::post('/', 'GalleryController@store');
        Route::post('/publish', 'GalleryController@publish');
        Route::post('/available', 'GalleryController@available');
        Route::get('/', 'GalleryController@list');
        Route::get('/update', 'GalleryController@update');
        Route::post('/update', 'GalleryController@update');
        Route::get('/delete', 'GalleryController@delete');
        Route::get('/home', 'GalleryController@home');
        Route::post('/{id}', 'GalleryController@update');
    });

    //shipping zone
    Route::group(['prefix' => 'shipping_zones'], function () {
        Route::post('/', 'ShippingZoneController@store');
        Route::get('/', 'ShippingZoneController@list');
        Route::get('/update', 'ShippingZoneController@update');
        Route::post('/update', 'ShippingZoneController@update');
        Route::get('/delete', 'ShippingZoneController@delete');
        Route::post('/{id}', 'ShippingZoneController@update');
        Route::get('/search', 'ShippingZoneController@search');
    });

    //sources
    Route::group(['prefix' => 'sources'], function () {
        Route::post('/', 'SourcesController@store');
        Route::post('/publish', 'SourcesController@publish');
        Route::post('/available', 'SourcesController@available');
        Route::get('/', 'SourcesController@list');
        Route::get('/update', 'SourcesController@update');
        Route::post('/update', 'SourcesController@update');
        Route::get('/delete', 'SourcesController@delete');
        Route::post('/{id}', 'SourcesController@update');
        Route::get('/search', 'SourcesController@search');
    });
    });

    Route::group(['prefix' => 'staffs'], function () {
        Route::post('login', 'StaffController@login');
    });
});
