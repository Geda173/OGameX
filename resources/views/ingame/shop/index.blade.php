@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

<div id="eventboxContent" style="display: none">
        <img height="16" width="16" src="/img/icons/3f9884806436537bdec305aa26fc60.gif">
    </div>


    <div id="inhalt">
        <div id="planet">
            <div id="header_text">
                <h2>
                    Shop            </h2>
            </div>

            <div id="detail" class="detail_screen small">
                <div id="techDetailLoading"></div>
            </div>

        </div>
        <div class="c-left"></div>
        <div class="c-right"></div>

        <div id="buttonz">
            <div class="header">
                <h2>Shop</h2>
            </div>
            <div class="content">
                <button class="to_shop active tooltip js_hideTipOnMobile" title="You can buy items here.">
                    <span class="to_shop_icon">Shop</span>
                </button>
                <button class="to_inventory tooltip js_hideTipOnMobile" title="You can get an overview of your purchased items here.">
        <span class="to_inventory_icon">
            Inventory            </span>
                </button>

                <div id="itemBox" class="border5px">
                    <div class="aside">
                        <ul class="listfilter border5px categoryFilter">
                            <li class="border5px inShop active">
                                <a href="javascript:void(0);" rel="c18170d3125b9941ef3a86bd28dded7bf2066a6a" class="active">
                            <span>
                                Special offers (<span class="amount">{{ $items->count() }}</span>)
                            </span>
                                </a>
                            </li>
                            <li class="border5px inShop inInventory">
                                <a href="javascript:void(0);" rel="d8d49c315fa620d9c7f1f19963970dea59a0e3be">
                            <span>
                                all (<span class="amount">30</span>)
                            </span>
                                </a>
                            </li>
                            <li class="border5px inShop inInventory">
                                <a href="javascript:void(0);" rel="e71139e15ee5b6f472e2c68a97aa4bae9c80e9da">
                            <span>
                                Resources (<span class="amount">12</span>)
                            </span>
                                </a>
                            </li>
                            <li class="border5px inShop inInventory">
                                <a href="javascript:void(0);" rel="cccaafe693a53e8d1e791f06327974539da5978f">
                            <span>
                                Buddy Items (<span class="amount">3</span>)
                            </span>
                                </a>
                            </li>
                            <li class="border5px inShop inInventory">
                                <a href="javascript:void(0);" rel="dc9ec90e5a2163cc063b8bb3e9fe392782f565c8">
                            <span>
                                Construction (<span class="amount">18</span>)
                            </span>
                                </a>
                            </li>
                        </ul>
                        <div class="btn_wrap">
                            <a href="#" tabindex="1" class="btn btn_confirm buyResourcesLink">
                                Get more resources                    </a>
                        </div>
                        <div class="btn_wrap">
                            <a role="button" tabindex="2" class="btn btn_confirm detail_button slideIn" ref="ffffffffffffffffffffffffffffffffffffffff">
                                Purchase Dark Matter                    </a>
                        </div>
                    </div>


                    <div id="js_shopSliderBox" class="shop_slider">
                        <div class="anythingSlider anythingSlider-default activeSlider" style="width: 335px; height: 332px;">
                            <div class="anythingWindow" style="width: 335px; height: 332px;">
                                <ul id="js_shopSlider" class="anythingBase horizontal" style="width: 335px; left: 0px;">
                                    <li class="slide_0 panel activePage" style="width: 335px; height: 332px;">
                                        @foreach($items as $item)
                                        <div class="item_img {{ $item->rarity_class }}" style="background-image: url(/img/icons/{{ $item->icon }});">
                                            <div class="item_img_box">
                                                <div class="activation disabled"></div>
                                                <a href="javascript:void(0);"
                                                   tabindex="1"
                                                   title="{{ $item->name }}|{!! $item->description !!}"
                                                   class="detail_button tooltipHTML js_hideTipOnMobile shop-item-link"
                                                   data-item-ref="{{ $item->ref_id }}"
                                                   data-item-id="{{ $item->id }}">
                                                    <div class="sale_badge disabled"></div>
                                                    <span class="ecke">
                                                        <span class="level price">{{ $item->formatted_price }}</span>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                        @endforeach
                                    </li>
                                </ul>
                            </div>
                            <div class="anythingControls" style="display: none;">
                                <ul class="thumbNav" style="display: none;"></ul>
                            </div>
                            <span class="arrow back disabled" style="display: none;"><a href="#"><span>«</span></a></span>
                            <span class="arrow forward disabled" style="display: none;"><a href="#"><span>»</span></a></span>
                        </div>
                    </div>

                    <div id="js_inventorySliderBox" class="inventory_slider" style="display:none;"></div>
                </div>        <div class="footer"></div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        // Handle clicking on shop items
        $(document).on('click', '.shop-item-link', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var itemId = $(this).data('item-id');
            console.log('Shop item clicked, item ID:', itemId);

            if (itemId) {
                loadItemDetails(itemId);
            }

            return false;
        });

        // Handle clicking on inventory items
        $(document).on('click', '.inventory-item-link', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var itemId = $(this).data('item-id');
            console.log('Inventory item clicked, item ID:', itemId);

            if (itemId) {
                loadItemDetails(itemId);
            }

            return false;
        });

        // Handle shop/inventory tab switching
        $('.to_shop').on('click', function() {
            showShop();
        });

        $('.to_inventory').on('click', function() {
            showInventory();
        });

        // Handle close button in overlay
        $(document).on('click', '.close_details', function() {
            $('#detail').fadeOut('fast');
        });
    });

    // Map of ref_ids to item IDs from the items collection
    var itemRefToIdMap = {
        @foreach($items as $item)
        '{{ $item->ref_id }}': {{ $item->id }},
        @endforeach
    };

    function findItemIdByRef(refId) {
        return itemRefToIdMap[refId] || null;
    }

    function loadItemDetails(itemId) {
        console.log('Loading item details for ID:', itemId);

        $.ajax({
            url: '{{ route('shop.ajax.itemdetails') }}',
            method: 'GET',
            data: {
                item_id: itemId
            },
            success: function(response) {
                console.log('AJAX response received:', response);

                if (response.content && response.content.technologydetails) {
                    $('#detail').html(response.content.technologydetails);
                    $('#detail').css({
                        'height': '250px',
                        'display': 'block',
                        'overflow': 'hidden',
                        'margin-top': '0px'
                    }).fadeIn('fast');

                    console.log('Item details loaded successfully');
                } else {
                    console.error('Invalid response format:', response);
                }
            },
            error: function(xhr) {
                console.error('AJAX error:', xhr);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    fadeBox(xhr.responseJSON.message, true);
                } else {
                    fadeBox('Failed to load item details', true);
                }
            }
        });
    }

    function showShop() {
        $('.to_shop').addClass('active');
        $('.to_inventory').removeClass('active');
        $('#js_shopSliderBox').show();
        $('#js_inventorySliderBox').hide();
    }

    function showInventory() {
        $('.to_shop').removeClass('active');
        $('.to_inventory').addClass('active');
        $('#js_shopSliderBox').hide();
        $('#js_inventorySliderBox').show();

        // Load inventory if not already loaded
        if ($('#js_inventorySliderBox').html().trim() === '') {
            loadInventory();
        }
    }

    function loadInventory() {
        $.ajax({
            url: '{{ route('shop.ajax.inventory') }}',
            method: 'GET',
            success: function(response) {
                if (response.success && response.html) {
                    $('#js_inventorySliderBox').html(response.html);
                }
            },
            error: function(xhr) {
                console.error('Failed to load inventory:', xhr);
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    fadeBox(xhr.responseJSON.message, true);
                }
            }
        });
    }

    // Expose for use in other scripts
    window.loadItemDetails = loadItemDetails;
    window.showShop = showShop;
    window.showInventory = showInventory;
</script>
@endsection
