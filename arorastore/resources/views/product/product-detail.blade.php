@extends('layouts.app')
@section('title', $product->pro_name)
@section('content')
    <div class="container">
        <div class="container-product p-5 mt-5">
            <div class="container-row">
                <div class="product-image">
                    <div id="produto">
                        <img class="demo" src="images/products/{{ $product->pro_image }}" alt="">
                    </div>
                    <ul id="haha">
                        @foreach ($product->proChillImages as $chill)
                            <li>
                                <img src="images/chillImageProducts/{{ $chill->chill_image }}" alt="">
                            </li>
                        @endforeach
                    </ul>
                    <div class="img-ads">
                        <img src="https://c0.anthill.vn/images/2020/11/19/6140b5c3f6078fc094ec22e606f64cb9.jpg" alt="">
                    </div>
                </div>
                <!-- end img -->
                <div class="brand">
                    <ul class="top-brand">
                        <li>@lang('main.categories') <a href="">{{ $product->category->cate_name }}</a></li>
                    </ul>
                    <div class="brand-header">
                        <ul>
                            <li><img src="./assets/images/logo_nne.png" alt=""></li>
                            <li>
                                {{ $product->pro_name }}
                            </li>
                            <li>
                                <div class="ratings">
                                    <div class="empty-stars"></div>
                                    <div class="full-stars" style="width:{{ $product->reviews->avg('rate') * 20 }}%"></div>
                                </div>
                                @lang('main.product.number_review', ['sodanhgia' => $product->reviews->count()])
                            </li>

                        </ul>
                    </div>
                    <div class="brand-content">
                        <div class="left">
                            <div class="price-and-icon">
                                <div class="flash-sale-price">
                                    <span>{{ number_format($product->pro_new_price) }} ???</span>
                                    <span>{{ FLOOR(100 - ($product->pro_new_price / $product->pro_old_price) * 100) }}%</span>
                                    <span>{{ number_format($product->pro_old_price) }}</span>
                                </div>
                            </div>
                            <div class="inner">
                                @lang('main.product.inner')
                            </div>
                            <form action="{{ route('addCart') }}" method="post">
                                @csrf
                                <div class="QualityInput__Wrapper">
                                    <p>@lang('main.cart.quantity')</p>
                                    <div class="group-input">
                                        <input style="width: 100px" class="form-control ml-5" type="number" value="1" min="1" max="{{ $product->quantity }}" name="quantity" class="input">
                                    </div>
                                    <input type="hidden" name="pro_id" value="{{ $product->pro_id }}">
                                    <div class="group-button mt-5 ml-5">
                                        @if (Auth::check())
                                            @if ($product->quantity > 0)
                                                <input type="hidden" name="pro_id" value="{{ $product->pro_id }}">
                                                <div class="yellow"><span>@lang('main.product.rest', ['quantity' => $product->quantity])</span></div>
                                                <button type="submit" class="btn btn-add-to-cart">@lang('main.cart.add')</button>
                                            @else
                                                <i class="text-danger text-center">@lang('main.product.hethang')</i>
                                            @endif
                                        @else
                                            <button type="button" onclick="alert(' @lang('main.acc.must_login') ')"
                                                class="btn btn-add-to-cart">@lang('main.cart.add')</button>
                                        @endif
                                    </div>
                                </div>
                            </form>
                            <div class="coupon">
                                @lang('main.product.coupon')
                                <div class="images">
                                    @foreach ($productOrderSame as $same)
                                        <a href="{{ route('product.show', $same->pro_id) }}">
                                            <img src="images/products/{{ $same->pro_image }}" alt="">
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="right">
                            <div class="seller-description">Cam k???t ch??nh hi???u b???i</div>
                            <!--  -->
                            <div class="seller-icon-and-name">
                                <img class="icon-store"
                                    src="https://salt.tikicdn.com/cache/w220/ts/seller/80/db/22/6de50458d7db49471069a181b36074c1.jpg">
                                <div>
                                    <a href="https://tiki.vn/cua-hang/cty-tnhh-tan-thien-phat" class="seller-name">
                                        <span>Cty TNHH T??n Thi??n Ph??t</span> <br>
                                        <span class="view-more">Xem Shop</span>
                                    </a>
                                </div>
                            </div>
                            <!--  -->
                            <div class="warranty-item">
                                <span class="itemLeft">Th???i gian b???o h??nh</span>
                                <span class="itemRight"> 6 th??ng</span>
                            </div>
                            <!--  -->
                            <div class="benefit-item">
                                <div class="compensation">
                                    <img
                                        src="https://frontend.tikicdn.com/_desktop-next/static/img/icons/compensation.svg">
                                    <br>
                                    <span>Ho??n ti???n<br><b>
                                            111%
                                        </b><br><a style="color:#242424; text-decoration: underline;"
                                            href="https://drive.google.com/file/d/1po3r6qApp-q7JDB5kwGKujVtvInfO-ih/view"
                                            target="_blank">n???u gi???</a></span>
                                </div>
                                <div class="guarantee">
                                    <img
                                        src="https://frontend.tikicdn.com/_desktop-next/static/img/pdp_revamp_v2/guarantee.svg">
                                    <br>
                                    <span>
                                        <!-- -->M??? h???p<br>ki???m tra<br>nh???n h??ng
                                        <!-- --> </span>
                                </div>
                                <div class="refund">
                                    <img src="https://frontend.tikicdn.com/_desktop-next/static/img/icons/refund.svg">
                                    <br>
                                    <span>?????i tr??? trong<br><b>7 ng??y</b><br>n???u sp l???i</span>
                                </div>
                            </div>
                            <!--  -->
                        </div>
                    </div>
                    
                </div>
                <!-- end brand -->
            </div>
        </div>
    </div>

    <!-- end brand -->
    <div class="container">
        <h2>@lang('main.product.product_same')</h2>
        <div class="container-product">

            <div class="container-row">
                <div class="custom-slider">
                    @foreach ($products as $pro)
                        @if ($pro->cate_id == $product->cate_id && $pro->pro_id != $product->pro_id)
                            <div class="custom-box">
                                <a href="{{ route('product.show', $pro->pro_id) }}">
                                    <img src="images/products/{{ $pro->pro_image }}" alt="">
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-9">
                <h2>@lang('main.product.product_desc')</h2>
                <div class="container-product">
                    <div style="padding: 20px 20px">
                        @php
                        Echo($product->pro_desc);
                        @endphp
                    </div>
                   
                </div>
            </div>
            <div class="col-3">
                <img  style="margin-top:45px" src="https://c0.anthill.vn/images/2020/11/16/46524b889f7d5522e3cd8552edf1351e.jpg" alt="">
            </div>
        </div>
        <div class="row">
            <div class="col-9">
                
                <h2>@lang('main.product.cus_review')</h2>
                <div class="container-product">
                    <form action="{{ route('review.store') }}" method="post">
                        @csrf
                        <div class="review-rating__inner">
                            <div class="review-rating__summary">
                                <div>
                                    <h3 class="mt-5">@lang('main.product.review')</h3>
                                </div>
                                <div class="review-rating__point text-center justtify-content-center">
                                    <span class="point_span">{{ round($product->reviews->avg('rate'), 1, PHP_ROUND_HALF_UP) }}</span><br>
                                    <div class="ratings">
                                        <div class="empty-stars"></div>
                                        <div class="full-stars" style="width:{{ $product->reviews->avg('rate') * 20 }}%"></div>
                                    </div>
                                </div>
                                <div class="review-rating__total">{{ $product->reviews->count() }}
                                    @lang('main.product.review')</div>
                            </div>
                            <!-- s??? ????nh gi?? -->
                            <div class="review-rating__detail">
                                <div class="review-rating__level mt-5">
                                    @if(Auth::check())
                                    <div class="stars">
                                        <input value="5" class="star star-5" id="star-5" type="radio" name="rate" />
                                        <label class="star star-5" for="star-5"></label>
                                        <input value="4" class="star star-4" id="star-4" type="radio" name="rate" />
                                        <label class="star star-4" for="star-4"></label>
                                        <input value="3" class="star star-3" id="star-3" type="radio" name="rate" />
                                        <label class="star star-3" for="star-3"></label>
                                        <input value="2" class="star star-2" id="star-2" type="radio" name="rate" />
                                        <label class="star star-2" for="star-2"></label>
                                        <input value="1" class="star star-1" id="star-1" type="radio" name="rate" />
                                        <label class="star star-1" for="star-1"></label>
                                    </div>
                                    @else
                                        <div class="star">
                                            <h1><i class="text-secondary">"@lang('main.acc.must_login_to_rate')"</i></h1>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <!-- chi ti???t s??? ????nh gi?? -->
                        </div>
                        <!-- ????y l?? ph???n s??? li???u ????nh gi?? -->
                        @foreach ($product->reviews as $rev)
                            <div class="review-comment__avatar">
                                <ul>
                                    <li class="ml-4"><img src="images/users/{{ $rev->user->avatar }}" alt=""></li>
                                    {{ $rev->user->fullname }}
                                    <li></li>
                                    <li>
                                        @if ($rev->user->orders->count() > 0)
                                            <i class="fas fa-comments"></i> @lang('main.product.order_check')
                                        @endif
                                    </li>
                                </ul>
                                <div class="review-comment-content ml-4">
                                    {{ $rev->comment }}
                                </div>
                        @endforeach
                        @if(Auth::check())
                        <div class="form-cmt">
                            <input id="last-name" type="text" name="comment" placeholder="{{ trans('main.product.cmt_placeholder') }}">
                            <input type="hidden" value="{{ $product->pro_id }}" name="pro_id">
                            <button id="submit" type="submit">@lang('main.product.send_review')</button>
                        </div>
                        @else
                        <div class="form-cmt text-center justify-content-center">
                            <i class="text-center text-secondary justify-content-center">"@lang('main.acc.must_login_to_cmt')"</i>
                        </div>
                        @endif
                </div>
                </form>
            </div>
        </div>
        <div class="col-3">
            
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#produto').zoom();
            $("#haha li img").click(function() { //khi click v??o th??? img
                var getId = $(this).attr("src"); // ?????t bi???n l?? getID = src ???nh c???a khi click
                console.log(getId); //xu???t ra xem
                $(".demo").attr("src",
                getId); //sau khi click th??? img c?? class demo s??? thay ?????i src m???c ?????nh sang src c???a getID(???nh ???????c click)
            })
            $("button#submit").click(function() {
                var submit = $("input#last-name").val();
                var getGender = $("input[type='radio']").is(":checked");
                var flag = true
                if (getGender != false) {
                    alert("???? ch???n")
                } else {
                    alert("ch??a ch???n")
                    flag = false;
                }
                if (submit == '') {
                    $("input#last-name").css({
                        "background": "rgba(255, 147, 146, 0.3)",
                        "border": "2px solid rgb(255, 0, 0, 0.3)"
                    });
                    flag = false
                    alert("B???n vui l??ng n???i dung v??o ph???n b??nh lu???n")
                } else {
                    $("input#last-name").css({
                        "background": "rgb(100 216 90 / 30%)",
                        "border": "2px solid rgb(147 161 146 / 30%)"
                    });
                }


                if (flag) {
                    alert("G???i b??nh lu???n th??nh c??ng")
                    return true
                }
                return false
            })
        })

    </script>


    </div>
@endsection
