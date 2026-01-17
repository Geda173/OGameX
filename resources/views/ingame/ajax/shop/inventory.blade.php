<div class="anythingSlider anythingSlider-default activeSlider" style="width: 335px; height: 332px;">
    <div class="anythingWindow" style="width: 335px; height: 332px;">
        <ul id="js_inventorySlider" class="anythingBase horizontal" style="width: 335px; left: 0px;">
            <li class="slide_0 panel activePage" style="width: 335px; height: 332px;">
                @foreach($inventoryItems as $inventoryItem)
                <div class="item_img {{ $inventoryItem['item']->rarity_class }}"
                     style="background-image: url(/img/icons/{{ $inventoryItem['item']->icon }});">
                    <div class="item_img_box">
                        <div class="activation disabled"></div>
                        <a href="javascript:void(0);"
                           tabindex="1"
                           title="{{ $inventoryItem['item']->name }}|{!! $inventoryItem['item']->description !!}|In Inventory: {{ $inventoryItem['quantity'] }}"
                           class="detail_button tooltipHTML js_hideTipOnMobile inventory-item-link"
                           data-item-id="{{ $inventoryItem['item']->id }}"
                           data-item-ref="{{ $inventoryItem['item']->ref_id }}">
                            <div class="sale_badge disabled"></div>
                            <span class="ecke">
                                <span class="level">{{ $inventoryItem['quantity'] }}</span>
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

<script type="text/javascript">
    // Inventory items will be handled by the main shop page JavaScript
    // through the .inventory-item-link class handler
</script>
