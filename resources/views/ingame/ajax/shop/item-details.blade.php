<div id="itemDetails" data-uuid="{{ $item->ref_id }}">
    <div class="detailsHolder">
        <div id="pic">
            <img src="/img/icons/{{ $item->icon }}" width="200" height="200" alt="{{ $item->name }}">
        </div>
        <div id="content">
            <h2>
                {{ $item->name }}
            </h2>
            <a id="close" class="close_details" ref="{{ $item->ref_id }}" href="javascript:void(0);">
            </a>
            <br class="clearfloat">

            <div id="wrapper">
                <div id="features">
                    <p class="extended_description">
                        {!! $item->description !!}
                        @if($item->duration !== 'now')
                        <span class="more_info blue_txt bold">
                            Duration: {{ $item->duration }}
                        </span>
                        @endif
                    </p>

                    @if($enoughDarkMatter)
                    <a class="build-it tooltip item dm js_hideTipOnMobile purchase-item-btn"
                       href="javascript:void(0);"
                       data-item-id="{{ $item->id }}"
                       data-tooltip-title="Purchase this item">
                        <span class="textlabel specialPrice">
                            Buy for:<br>
                            @if($item->hasDiscount())
                            <span style="text-decoration: line-through"><em>{{ number_format($item->price) }} DM</em></span><br>
                            {{ number_format($item->getDiscountedPrice()) }} DM
                            @else
                            {{ number_format($item->price) }} DM
                            @endif
                        </span>
                    </a>
                    @else
                    <a class="build-it_disabled tooltip item dm js_hideTipOnMobile"
                       href="javascript:void(0);"
                       data-tooltip-title="There is not enough dark matter available">
                        <span class="textlabel specialPrice">
                            Buy for:<br>
                            @if($item->hasDiscount())
                            <span style="text-decoration: line-through"><em>{{ number_format($item->price) }} DM</em></span><br>
                            {{ number_format($item->getDiscountedPrice()) }} DM
                            @else
                            {{ number_format($item->price) }} DM
                            @endif
                        </span>
                    </a>
                    @endif

                    @if($inventoryCount > 0)
                    <a class="build-it_enabled
                        activateItem
                        tooltip js_hideTipOnMobile
                        "
                       href="javascript:void(0);"
                       data-item-id="{{ $item->id }}"
                       data-item-name="{{ $item->name }}"
                       data-tooltip-title="Activate">
                        <span class="textlabel">
                            Activate ({{ $inventoryCount }})
                        </span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div id="description">
        <p>
            @if($item->effect_type === 'reduce_building_time')
            Reduces building time by {{ $item->effect_duration }}
            @elseif($item->effect_type === 'reduce_shipyard_time')
            Reduces shipyard construction time by {{ $item->effect_duration }}
            @elseif($item->effect_type === 'reduce_research_time')
            Reduces research time by {{ $item->effect_duration }}
            @else
            {{ $item->name }}
            @endif
        </p>
    </div>
</div>
<script type="text/javascript">
    var loca = loca || {}
    loca.buyDMDecision = 'Not enough Dark Matter available! Do you want to buy some now?'

    $(document).ready(function() {
        // Handle purchase button click
        $('.purchase-item-btn').on('click', function() {
            var itemId = $(this).data('item-id');
            purchaseItem(itemId);
        });

        // Handle activate button click (to be implemented)
        $('.activateItem').on('click', function() {
            var itemId = $(this).data('item-id');
            var itemName = $(this).data('item-name');
            // TODO: Implement activation logic
            alert('Activation of ' + itemName + ' will be implemented soon!');
        });
    });

    function purchaseItem(itemId) {
        $.ajax({
            url: '{{ route('shop.ajax.purchase') }}',
            method: 'POST',
            data: {
                item_id: itemId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    fadeBox(response.message, false);

                    // Update dark matter display
                    updateDarkMatterDisplay(response.darkMatter);

                    // Close overlay and reload shop
                    closeOverlay();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    fadeBox(response.message, true);
                }
            },
            error: function(xhr) {
                var message = 'An error occurred';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                fadeBox(message, true);
            }
        });
    }

    function updateDarkMatterDisplay(newAmount) {
        // Update the dark matter counter in the UI
        // TODO: Find and update the actual dark matter display element
        console.log('Dark Matter updated to:', newAmount);
    }

    function closeOverlay() {
        $('#detail').fadeOut('fast');
    }
</script>
