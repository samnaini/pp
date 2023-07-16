(($) => {
    dayjs.extend(window.dayjs_plugin_advancedFormat);

    const $pp_tracking_section = $('.pp-tracking-section')
        , $loading = $pp_tracking_section.children('.loading-container')
        , $box_form_od_em = $pp_tracking_section.find('.box-form-od-em')
        , $box_form_tn = $pp_tracking_section.find('.box-form-tn')
        , $btn_tracking_enter = $('.tracking-form .btn-enter')
        , $input_order_number = $('#pp-tracking-ipt-on')
        , $input_email = $('#pp-tracking-ipt-em')
        , $input_tracking_number = $('#pp-tracking-ipt-tn')
        , $tracking_result_title = $pp_tracking_section.children('.tracking-result-title')
        , $translate_element = $pp_tracking_section.children('#pp-google-translate-element')
        , $tracking_title_text = $tracking_result_title.children('.title')
        , $order_nums = $tracking_result_title.children('.order-nums')

    const GOOGLE_MAP_KEY = 'AIzaSyCMG-OWhqs5GTIELSzqQCwyC0dLQWMu81s'

    const ASSETS_PATH = pp_tracking_params.assets_path

    const DISPLAY_OPTION = pp_track_config.display_option
    const TRANSLATIONS = pp_track_config.tracking_page_translations

    const PRODUCT_RECOMMEND = pp_track_config.product_recommend
    const RECOMMEND_PRODUCTS = pp_track_config.recommend_products

    const STATUS_CONFIG = {
        blank: {
            status: 0,
            img: get_status_icon_url('blank'),
        },
        pending: {
            status: 1,
            img: get_shipment_status_icon_url_2('pending'),
        },
        transit: {
            status: 2,
            img: get_shipment_status_icon_url_2('in_transit'),
        },
        pickup: {
            status: 3,
            img: get_shipment_status_icon_url_2('out_for_delivery'),
        },
        delivered: {
            status: 4,
            img: get_shipment_status_icon_url_2('delivered'),
        },
        expired: {
            status: 5,
            img: get_shipment_status_icon_url_2('expired'),
        },
        undelivered: {
            status: 6,
            img: get_shipment_status_icon_url_2('undelivered'),
        },
        exception: {
            status: 7,
            img: get_shipment_status_icon_url_2('exception'),
        },
        info_received: {
            status: 8,
            img: get_shipment_status_icon_url_2('info_received'),
        },
    }

    const STATUS_NAME_IMG = {
        1001: {
            svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M17 18c-.551 0-1-.449-1-1 0-.551.449-1 1-1 .551 0 1 .449 1 1 0 .551-.449 1-1 1zM4 17c0 .551-.449 1-1 1-.551 0-1-.449-1-1 0-.551.449-1 1-1 .551 0 1 .449 1 1zM17.666 5.841L16.279 10H4V4.133l13.666 1.708zM17 14H4v-2h13a1 1 0 0 0 .949-.684l2-6a1 1 0 0 0-.825-1.308L4 2.117V1a1 1 0 0 0-1-1H1a1 1 0 0 0 0 2h1v12.184A2.996 2.996 0 0 0 0 17c0 1.654 1.346 3 3 3s3-1.346 3-3c0-.353-.072-.686-.184-1h8.368A2.962 2.962 0 0 0 14 17c0 1.654 1.346 3 3 3s3-1.346 3-3-1.346-3-3-3z"></path></svg>',
        },
        custom: {
            svg: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.707 9.293l-5-5a.999.999 0 1 0-1.414 1.414L14.586 9H3a1 1 0 1 0 0 2h11.586l-3.293 3.293a.999.999 0 1 0 1.414 1.414l5-5a.999.999 0 0 0 0-1.414" fill-rule="evenodd"></path></svg>',
            msvg: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 17.707l5-5a.999.999 0 1 0-1.414-1.414L11 14.586V3a1 1 0 1 0-2 0v11.586l-3.293-3.293a.999.999 0 1 0-1.414 1.414l5 5a.999.999 0 0 0 1.414 0" fill-rule="evenodd"></path></svg>',
        },
        1100: {
            svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M19.901 4.581c-.004-.009-.002-.019-.006-.028l-2-4A1.001 1.001 0 0 0 17 0H3c-.379 0-.725.214-.895.553l-2 4c-.004.009-.002.019-.006.028A.982.982 0 0 0 0 5v14a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V5a.982.982 0 0 0-.099-.419zM2 18V6h7v1a1 1 0 0 0 2 0V6h7v12H2zM3.618 2H9v2H2.618l1-2zm13.764 2H11V2h5.382l1 2zM9 14H5a1 1 0 0 0 0 2h4a1 1 0 0 0 0-2m-4-2h2a1 1 0 0 0 0-2H5a1 1 0 0 0 0 2"></path></svg>',
        },
        transit: {
            name: TRANSLATIONS.in_transit.replace(/ /g, '&nbsp;'),
            svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M17.816 14c-.415-1.162-1.514-2-2.816-2s-2.4.838-2.816 2H12v-4h6v4h-.184zM15 16c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zM5 16c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zM2 4h8v10H7.816C7.4 12.838 6.302 12 5 12s-2.4.838-2.816 2H2V4zm13.434 1l1.8 3H12V5h3.434zm4.424 3.485l-3-5C16.678 3.185 16.35 3 16 3h-4a1 1 0 0 0-1-1H1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h1.185C2.6 17.162 3.698 18 5 18s2.4-.838 2.816-2h4.37c.413 1.162 1.512 2 2.814 2s2.4-.838 2.816-2H19a1 1 0 0 0 1-1V9c0-.18-.05-.36-.142-.515z"></path></svg>',
            status: 2,
        },
        pickup: {
            name: TRANSLATIONS.out_for_delivery.replace(/ /g, '&nbsp;'),
            svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M10 0C5.589 0 2 3.589 2 8c0 7.495 7.197 11.694 7.504 11.869a.996.996 0 0 0 .992 0C10.803 19.694 18 15.495 18 8c0-4.412-3.589-8-8-8m-.001 17.813C8.478 16.782 4 13.296 4 8c0-3.31 2.691-6 6-6s6 2.69 6 6c0 5.276-4.482 8.778-6.001 9.813M10 10c-1.103 0-2-.897-2-2s.897-2 2-2 2 .897 2 2-.897 2-2 2m0-6C7.794 4 6 5.794 6 8s1.794 4 4 4 4-1.794 4-4-1.794-4-4-4"></path></svg>',
            status: 3,
        },
        delivered: {
            name: TRANSLATIONS.delivered.replace(/ /g, '&nbsp;'),
            svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M10 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8m0-14c-3.309 0-6 2.691-6 6s2.691 6 6 6 6-2.691 6-6-2.691-6-6-6m-1 9a.997.997 0 0 1-.707-.293l-2-2a.999.999 0 1 1 1.414-1.414L9 10.586l3.293-3.293a.999.999 0 1 1 1.414 1.414l-4 4A.997.997 0 0 1 9 13"></path></svg>',
            status: 4,
        },
    }

    let is_processing = false

    // 已完成初始化的任务
    const initialized_tasks = {}

    let progress_width_list = []

    let map_info_list = []

    let g_is_preview = false
    let g_order_number = ''
    let g_email = ''
    let g_tracking_number = ''

    let product_list = []

    let tracking_recommend_products = undefined

    // 表单提交事件
    $btn_tracking_enter.on('click', function (e) {

        if (is_processing) {
            return
        }

        is_processing = true

        // 输入异常
        let is_error = false

        // 输入框对象
        let input_elements = []

        // 请求数据
        let input_data = {}

        let _url = window.location.href

        _url = wp.url.removeQueryArgs(_url, 'preview')

        if ('pp-btn-trk-1' === e.target.id) {
            // 使用 Order Number、Email 查询
            input_elements = [$input_order_number, $input_email]
            _url = wp.url.removeQueryArgs(_url, $input_tracking_number.attr('name'))
        } else {
            // 使用 Tracking Number 查询
            input_elements = [$input_tracking_number]
            _url = wp.url.removeQueryArgs(_url, 'token', $input_order_number.attr('name'))
        }

        // 判空检测
        input_elements.forEach(e => {
            let value = e.val().trim()
            if ('' === value) {
                // 显示提示
                e.parent().find('.tip').show()
                // 存在错误
                is_error = true
            } else {
                // 隐藏提示
                e.parent().find('.tip').hide()
                // 获取表单名称
                const name = e.attr('name')
                input_data[name] = value
            }
        })

        if (!is_error) {
            // 复位翻译插件
            reset_translate_element()
            // 清空
            $pp_tracking_section.children('.tracking-result').remove()
            $pp_tracking_section.children('.pp_recommend_product_parent').remove()
            // 隐藏 title
            $tracking_result_title.hide()

            const query_data = {}

            for (const k in input_data) {
                const v = input_data[k]
                if ('email' === k) {
                    query_data['token'] = v.includes('@') ? v.split('').reverse().join('').replace('@', '_-_') : v
                } else {
                    query_data[k] = v
                }
            }

            _url = wp.url.addQueryArgs(_url, query_data)
            history.pushState('', '', _url)

            // 请求
            ajax_get_track_info(input_data)
                .done(resp => {
                    is_processing = false

                    if (!resp.success) {
                        resp.msg && alert(resp.msg)
                        return
                    }

                    g_is_preview = resp.data.is_preview
                    g_order_number = resp.data.order_number
                    g_email = resp.data.email
                    g_tracking_number = resp.data.tracking_number
                    product_list = resp.data.product

                    fill_input()

                    tracking_recommend_products = resp.data.recommend_products

                    init_tracking_result(resp.data.tracking)
                })

            return
        }

        is_processing = false
    })

    // 监听左输入框回车事件
    $box_form_od_em.on('keyup', function (e) {
        if (13 === e.keyCode) {
            $('#pp-btn-trk-1').trigger('click')
        }
    })

    // 监听右输入框回车事件
    $box_form_tn.on('keyup', function (e) {
        if (13 === e.keyCode) {
            $('#pp-btn-trk-2').trigger('click')
        }
    })

    if (typeof pp_tracking_data !== 'undefined' && pp_tracking_data.tracking) {

        g_is_preview = pp_tracking_data.is_preview
        g_order_number = pp_tracking_data.order_number
        g_email = pp_tracking_data.email
        g_tracking_number = pp_tracking_data.tracking_number
        product_list = pp_tracking_data.product

        fill_input()

        tracking_recommend_products = pp_tracking_data.recommend_products

        init_tracking_result(pp_tracking_data.tracking)
    }

    // 切换单号事件
    $order_nums.on('click', 'span', function () {
        const index = $(this).data('id')

        if (undefined === index) {
            return
        }

        $(this).addClass('active')
            .siblings('span')
            .removeClass('active')

        const $tracking_result = $pp_tracking_section.children('.tracking-result')

        $tracking_result.removeClass('active')
            .eq(index).addClass('active')

        init_map(index)

        display_translate_element(index)

        progress_animate(index, progress_width_list[index])
    })

    function fill_input() {
        let [order_number, email,  tracking_number] = [g_order_number, g_email, g_tracking_number]

        if (!g_is_preview) {
            if (tracking_number?.length) {
                // 清除 订单号、邮箱 输入框
                order_number = ''
                email = ''
            } else {
                // 清除 物流单号 输入框
                tracking_number = ''
            }
        }

        $input_order_number.val(order_number)
        $input_email.val(email)
        $input_tracking_number.val(tracking_number)
    }


    /**
     * 显示谷歌翻译插件
     */
    function display_translate_element(i) {
        const $skiptranslate = $pp_tracking_section.find('.skiptranslate')

        $pp_tracking_section
            .children('.tracking-result')
            .eq(i)
            .find('.google-translate-element')
            .append($skiptranslate)
    }

    /**
     * 翻译插件回位
     */
    function reset_translate_element() {
        const $skiptranslate = $pp_tracking_section.find('.skiptranslate')

        const parent_id = $skiptranslate.parent().attr('id')
        if ('pp-google-translate-element' !== parent_id) {
            $translate_element.append($skiptranslate)
        }
    }

    /**
     * 按需加载地图组件
     */
    function init_map(index) {
        if (!map_info_list[index]) {
            return false
        }

        const {is_loaded, shipping_map} = map_info_list[index]

        if (is_loaded || is_empty_object(shipping_map)) {
            return false
        }

        const $map = $(`#pp-map${index}`)

        if ($map.length) {

            map_info_list[index].is_loaded = true

            const {
                location,
                ship,
            } = shipping_map

            const map_obj = {
                index: index,
                location_type: 0 === ship ? TRANSLATIONS.current_location : TRANSLATIONS.shipping_to,
                location_address: location,
            }

            $map.append(load_google_map(map_obj))
        }
    }

    function load_google_map(obj) {
        if ('' === obj.location_address || !obj.location_address) return ''
        return `<div class="PP-GoogleMap"><iframe onload="pp_google_map_loaded(${obj.index})" allowfullscreen style="position:absolute;width:100%;height:100%;border:0;left:0;top:0;" src="https://www.google.com/maps/embed/v1/place?zoom=6&key=${GOOGLE_MAP_KEY}&q=${encodeURIComponent(obj.location_address)}"></iframe><div class="PP-GoogleMap-PlaceCard"><h5 class="PP-GoogleMap-PlaceCard__LocationType">${obj.location_type}</h5><p class="PP-GoogleMap-PlaceCard__LocationAddress">${obj.location_address}</p></div></div>`
    }

    /**
     * Ajax 获取 Track Info
     */
    function ajax_get_track_info({order, email, nums}) {
        // 加载动画
        $loading.show()

        return $.ajax({
            type: 'GET',
            url: pp_tracking_params.ajax_url,
            dataType: 'json',
            data: {
                action: 'pp_track_info',
                _ajax_nonce: pp_tracking_params.get_track_info_nonce,
                order,
                email,
                nums,
            },
        }).done(() => {
            // 隐藏加载动画
            $loading.hide()
        })
    }

    /**
     * 渲染 Track Info 页面
     * @param track_info
     */
    function init_tracking_result(track_info) {

        const is_empty_trackinfo = 0 === track_info.length

        const is_multi_order_nums = track_info.length > 1

        const order_arr = []

        if (is_multi_order_nums) {
            $order_nums.addClass('multi')
        } else {
            $order_nums.removeClass('multi')
        }

        let show_result_id = null

        let str = ''

        /////////// 轮播图
        let _reco_prod = []
        if (PRODUCT_RECOMMEND.enabled) {
            if (tracking_recommend_products === undefined) {
                // 取全局配置的推荐商品（按用户配置获取的真实的商品数据）
                _reco_prod = RECOMMEND_PRODUCTS
            } else if (tracking_recommend_products.length) {
                // 取当前tracking的推荐商品（目前用于展示预览商品用的）
                _reco_prod = tracking_recommend_products
            }
        }
        const prod_count = _reco_prod.length
        const get_product_recommend_swiper_dom = (is_show_title = true) => {
            if (!_reco_prod?.length) return
            const products_doms = _reco_prod.map(v => (
                `<div class="product swiper-slide"><div class="product-recommendation__card"><a ${v.url ? `href="${v.url}"` : ''} target="_blank" class="product__anchor" rel="noopener noreferrer"><img src="${v.img}" alt="${v.title}" class="product__img"><p class="product__title">${v.title}</p><p class="product__price">${v.price_html}</p></a></div></div>`
            ))
            const title = is_show_title
                ? `<h3 class="pp_recommend_title">${TRANSLATIONS.may_like}</h3>`
                : ''
            return `<div class="pp_recommend_product_parent swiper">${title}<div class="swiper-button-next"></div><div class="swiper-button-prev"></div><div class="products swiper-wrapper">${products_doms.join('')}</div></div>`
        }

        if (PRODUCT_RECOMMEND.enabled && prod_count && PRODUCT_RECOMMEND.position === 0) {
            str += get_product_recommend_swiper_dom(true)
        }

        progress_width_list = []

        map_info_list = []

        track_info.forEach((v, k) => {

            const {
                tracking_number,
                shipping_map,
                status_node,
                product: shipment_product = [],
            } = v
            const status_number = v.status_data_num || 0
                , custom_status = v.status_num.status || status_number
                , custom_status_name = v.status_num.name || v.status
                , custom_status_description = v.status_num.status_description || ''

            const {
                name: carrier_name,
                url: carrier_url,
                tel: carrier_phone,
                img: carrier_img,
            } = v.carrier

            const show_edt = v.shipping_time_show && (pp_track_config.estimated_delivery_time.enabled || v.scheduled_delivery_date)

            if (
                null === show_result_id
                ||
                (tracking_number && g_tracking_number === tracking_number)
            ) {
                // 显示首页 或 对应单号页
                show_result_id = k
            }


            if (!is_multi_order_nums) {
                order_arr.push(`<span data-id=${k}>${g_order_number}</span>`)
            } else {
                order_arr.push(`<span data-id=${k}>${g_order_number}-F${k + 1}</span>`)
            }

            str += `<div class="tracking-result" data-id=${k}>`
            let [_str, _progress_width] = get_progress_bar_style(status_node, DISPLAY_OPTION.color, custom_status)

            str += _str

            progress_width_list.push(_progress_width)

            str += '<div class="result-left">'

            const is_custom_status = (parseInt(status_number) === 1 && custom_status !== status_number && custom_status_name)

            // Do custom processing for unresolved ticket numbers
            str += `<h2 class="pp_num_status_show">${TRANSLATIONS.status}: ${is_custom_status ? custom_status_name : v.status}</h2>`
            if (is_custom_status) {
                str += `<div class="pp_tracking_status_tips">${custom_status_description}</div>`
            }

            // this add map
            if (DISPLAY_OPTION.map_coordinates) {
                str += `<div id="pp-map${k}" class="pp-map-container" style="display:none"></div>`
            }

            // shipping_time get date
            let shipping_time_con_a = v.shipping_time_con ? v.shipping_time_con.split("-") : []
            if (shipping_time_con_a.length > 1) {
                let startT = shipping_time_con_a[0] ? shipping_time_con_a[0].replace(" ", "") : 0
                let endT = shipping_time_con_a[1] ? shipping_time_con_a[1].replace(" ", "") : 0
                let start_time = formatTime(startT * 1000, pp_track_config?.date_and_time_format?.date_format ?? 0, pp_track_config?.date_and_time_format?.time_format ?? 0, {
                    is_hidden_year: false,
                    is_hidden_time: true,
                    has_second: false
                })
                let end_time = formatTime(endT * 1000, pp_track_config?.date_and_time_format?.date_format ?? 0, pp_track_config?.date_and_time_format?.time_format ?? 0, {
                    is_hidden_year: false,
                    is_hidden_time: true,
                    has_second: false
                })
                v.shipping_time_con = start_time + ' - ' + end_time
            } else {
                if (v.shipping_time_con && v.shipping_time_con.length === 10) {
                    v.shipping_time_con = formatTime(v.shipping_time_con * 1000, pp_track_config?.date_and_time_format?.date_format ?? 0, pp_track_config?.date_and_time_format?.time_format ?? 0, {
                        is_hidden_year: false,
                        is_hidden_time: true,
                        has_second: false
                    })
                }
            }

            // show shipping time（plan to time）
            if (window.innerWidth <= 600) {
                if (show_edt) {
                    str += `<ul class="pp_shipping_md"><li><div class="pp_tracking_info_title"><span>${TRANSLATIONS.expected_delivery}</span></div><div class="pp_tracking_info"><span>${v.shipping_time_con}</span></div></li></ul>`
                }
            }

            if (DISPLAY_OPTION.tracking_detailed_info) {
                str += '<ul class="pp_tracking_result_parent pp_timeline"></ul>'
            }


            // + 右
            str += '</div><div class="result-right"><ul class="pp_tracking_info_parent">'

            // show shipping time（plan to time）
            if (600 < window.innerWidth) {
                if (show_edt) {
                    str += `<li><div class="pp_tracking_info_title"><span>${TRANSLATIONS.expected_delivery}</span></div><div class="pp_tracking_info"><span>${v.shipping_time_con}</span></div></li>`
                }
            }

            // Show carrier name and logo on your tracking page
            if (DISPLAY_OPTION.carrier_details && carrier_img && carrier_name) {
                str += `<li><div class="pp_tracking_info_title"><span>${TRANSLATIONS.carrier}</span></div><div class="pp_tracking_info"><div class="PP-CarrierInfo"><a class="pp_tracking_carrier_img" style="background-image:url(${carrier_img})" href="${carrier_url}" title="${carrier_name}" target="_blank" rel="noopener noreferrer"></a><div class="pp_tracking_carrier_info"><div class="pp_tracking_carrier_top"><span>${carrier_name}</span></div><div class="pp_tracking_carrier_bottom"><span><a href="tel:${carrier_phone}" rel="noopener noreferrer">${carrier_phone}</a></span></div></div></div></div></li>`
            }

            // Show tracking number on your tracking page
            if (DISPLAY_OPTION.tracking_number && tracking_number) {
                str += `<li><div class="pp_tracking_info_title"><span>${TRANSLATIONS.tracking_number}</span></div><div class="pp_tracking_info"><span>${tracking_number}</span></div></li>`
            }

            // Show the product and quantity contents of the package
            if (DISPLAY_OPTION.package_contents_details && product_list.length) {
                const product_list_html = get_product_list_html(shipment_product)
                str += `<li><div class="pp_tracking_info_title"><span>${TRANSLATIONS.product}</span></div><div class="pp_tracking_info pp-trk-product">${product_list_html}</div></li>`
            }

            // google translate
            if (DISPLAY_OPTION.google_translate_widget) {
                str += `<li><div class="pp_tracking_info_title"><div class="google-translate-element"></div></div></li>`
                // } else {
                //   $('.skiptranslate').remove()
                //   $('.goog-te-spinner-pos').remove()
            }

            if (PRODUCT_RECOMMEND.enabled && prod_count && PRODUCT_RECOMMEND.position === 2) {
                str += `<li class="PP-SectionItem PP-Section-ProductRecommended"><div class="pp_tracking_info_title"><span>${TRANSLATIONS.may_like}</span></div><div class="pp_tracking_info">${get_product_recommend_swiper_dom(false)}</div></li>`
            }

            str += '</ul></div></div>'

            // 地图数据
            if (DISPLAY_OPTION.map_coordinates && Object.prototype.toString.call(shipping_map) === '[object Object]') {
                map_info_list[k] = {
                    map_obj: null,
                    shipping_map: shipping_map,
                }
            }
        })

        if (PRODUCT_RECOMMEND.enabled && prod_count && PRODUCT_RECOMMEND.position === 1) {
            str += get_product_recommend_swiper_dom(true)
        }

        if (is_empty_trackinfo) {
            // 空物流信息
            // 变更标题为 Order Not Found
            $tracking_title_text.html(TRANSLATIONS.order_not_found)
        } else {
            // 变更标题为 Order
            $tracking_title_text.html(TRANSLATIONS.order)
            // 嵌入 Track Result DOM 元素
            $pp_tracking_section.append(str)
        }

        // Order 单号
        $order_nums.html(order_arr.join(', '))

        // 显示 title
        $tracking_result_title.show()

        if (null === show_result_id) {
            return
        }

        const $order_num_x = $order_nums.children(`span[data-id="${show_result_id}"]`).addClass('active')
        const $tracking_result_x = $pp_tracking_section.children(`.tracking-result[data-id="${show_result_id}"]`).addClass('active')

        if (!is_empty_trackinfo) {
            // 加载 tracking 信息
            init_track_info(track_info)
            // 加载地图
            init_map(show_result_id)
        }

        init_ok('tracking_result')

        setTimeout(() => {
            // 自动滚动到指定位置动画
            $('html,body').animate({scrollTop: $tracking_result_title.offset().top})
        }, 100)

        // 执行动画
        progress_animate(show_result_id, progress_width_list[show_result_id])


        ///// init recommend product swiper
        if (PRODUCT_RECOMMEND.enabled && prod_count) {
            // 在右边时只显示1个
            const pp_slides_per_view = PRODUCT_RECOMMEND.position === 2 ? 1 : 4
            new PPSwiper('.pp_recommend_product_parent', {
                autoHeight: true,
                slidesPerView: 1,
                loop: pp_slides_per_view <= prod_count,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    // when window width is >= 640px
                    640: {
                        slidesPerView: pp_slides_per_view,
                        spaceBetween: 24,
                    },
                },
            })
        }
    }

    /**
     * 获取商品列表
     */
    function get_product_list_html(product) {

        const IMG_NUM_LIMIT = 3

        let str = ''

        const isShowAll = !product?.length

        const get_link_html = (content, link, classname) => {
            return `<a ${link ? `href="${link}"` : ''} target="_blank" class="${classname}" rel="noopener noreferrer">${content}</a>`
        }

        if (isShowAll) {
            product_list
                .forEach((v, i) => {
                    const {image_url, name, quantity, sku, link} = v

                    if (!name) {
                        return
                    }

                    let img_html = i < IMG_NUM_LIMIT && image_url ? get_link_html(`<img src="${image_url}">`, link, 'pp-product-img-link') : ''

                    let product_name_html = `<span>x${quantity} ${name}</span>`

                    let content_html = `${img_html}${get_link_html(product_name_html, link, 'pp-product-name-link')}`

                    str += `<div class="pp-trk-product__item">${content_html}</div>`
                })
        } else {
            product.forEach((v, i) => {
                const {id, quantity} = v

                const {
                    image_url,
                    name,
                    quantity: _total_quantity,
                    sku,
                    link
                } = product_list.find(v => v.id === id) || {}

                if (!name) {
                    return
                }

                let img_html = i < IMG_NUM_LIMIT && image_url ? get_link_html(`<img src="${image_url}">`, link, 'pp-product-img-link') : ''

                let product_name_html = `<span>x${quantity || _total_quantity} ${name}</span>`

                let content_html = `${img_html}${get_link_html(product_name_html, link, 'pp-product-name-link')}`

                str += `<div class="pp-trk-product__item">${content_html}</div>`
            })
        }

        return `<div class="pp_tracking_product_show">${str}</div>`
    }

    function progress_animate(i, percent) {
        $(`.progress-bar-style:eq(${i}) div span`)
            .css('width', 0)
            .delay(200)
            .animate({width: `${percent}%`}, 1000)
    }

    window.onresize = function () {
        //   change_input_width()
    }

    // change_input_width()

    function change_input_width() {

        if (!document.querySelector('.pp_tracking_form_in')) return

        var form_in_width_first = document.querySelector('.pp_tracking_form_in').offsetWidth

        form_in_width_first = parseInt(form_in_width_first)

        var pp_tracking_form_order = document.querySelector('.pp_tracking_form_order'),
            pp_tracking_form_number = document.querySelector('.pp_tracking_form_number')

        if (form_in_width_first >= 1158) {
            if (pp_tracking_form_order) {
                pp_tracking_form_order.style.padding = '20px 130px'
            }
            if (pp_tracking_form_number) {
                pp_tracking_form_number.style.padding = '20px 130px 96px'
            }
        } else if (form_in_width_first < 1158 && form_in_width_first >= 982) {
            if (pp_tracking_form_order) {
                pp_tracking_form_order.style.padding = '20px 100px'
            }
            if (pp_tracking_form_number) {
                pp_tracking_form_number.style.padding = '20px 100px 96px'
            }
        } else if (form_in_width_first < 982 && form_in_width_first >= 714) {
            if (pp_tracking_form_order) {
                pp_tracking_form_order.style.padding = '20px 80px'
            }
            if (pp_tracking_form_number) {
                pp_tracking_form_number.style.padding = '20px 80px 96px'
            }
        } else {
            if (pp_tracking_form_order) {
                pp_tracking_form_order.style.padding = '20px'
            }
            if (pp_tracking_form_number) {
                pp_tracking_form_number.style.padding = '20px'
            }
        }
    }

    function get_progress_bar_style(status_node, config_color, status_number) {

        const status_keys = pp_track_config.status_keys

        let str = ''

        const status_arr = {transit: 2, pickup: 3, delivered: 4}
            , progress_arr = []

        const status_name_img = $.extend(true, {}, STATUS_NAME_IMG)

        status_keys.forEach(status => {

            if (status_node[status]) {

                const img = status_name_img[status] || status_name_img.custom

                progress_arr.push({
                    name: status_node[status].name.replace(/ /g, '&nbsp;'),
                    time: (status_node[status].date || '').replace(/ /g, '&nbsp;'),
                    svg: img.svg,
                    msvg: img.msvg || '',
                    status: status,
                })
            }
        })

        $.each(status_arr, (key, status) => {

            const status_info = status_node[status] || {}

            status_name_img[key].time = (status_info.date || '').replace(/ /g, '&nbsp;')

            if (status_info.name) {
                status_name_img[key].name = status_info.name.replace(/ /g, '&nbsp;')
            }

            progress_arr.push(status_name_img[key])
        })

        // Calculate the average length based on the number of nodes
        const length = progress_arr.length
            , every_left = Math.floor(100 / (length - 1))

        let font_size = 1
            , over_width = 0.5
            , node_count = 0

        switch (length) {
            case 5:
                font_size = 0.9375
                over_width = 3
                node_count = 0
                break
            case 6:
                font_size = 0.875
                over_width = 3
                node_count = 1
                break
            case 7:
                font_size = 0.8125
                over_width = 0.3
                node_count = 2
                break
            case 8:
                font_size = 0.78125
                over_width = 0.4
                node_count = 3
                break
        }

        // // use own icon change
        // var old_icon_len = progress_arr.length
        // if (statu_icon_arr.length) {
        //
        //   if (old_icon_len === statu_icon_arr.length) {
        //     for (var i = 0; i < old_icon_len; i++) {
        //       progress_arr[i].svg = '<img src="' + statu_icon_arr[i] + '" />'
        //     }
        //   }
        //
        // }

        // Get the length of the progress bar according to the current state and the number of nodes
        let process_width = get_process_width(over_width, status_number, every_left, node_count)

        str += `<div class="progress-bar-style"><div><span style="background:${config_color};width:${process_width}%"></span></div>`

        let left = 0
            , color_str = '', progress_time

        progress_arr.forEach((v, i) => {

            progress_time = formatTime(v.time * 1000, pp_track_config?.date_and_time_format?.date_format ?? 0, pp_track_config?.date_and_time_format?.time_format ?? 0, {
                is_hidden_year: true,
                is_hidden_time: true,
                has_second: false
            })

            color_str = left > process_width ? '' : `background:${config_color};`
            str += `<span class="progress-bar-node${color_str ? '' : ' progress-bar-node-disabled'}" style="left:${left}%;font-size:${font_size}rem;${color_str}">${v.svg}<span><b title="${v.name}">${v.name}</b>`
            if (v.time) {
                str += ` <span> ${progress_time}</span>`
            }
            str += '</span></span>'

            if (i === length - 2) {
                left = 100
            } else {
                left += every_left
            }
        })

        str += '</div>'

        // Add mobile progress bar style
        if (process_width < 100) {
            process_width += 2
        }

        str += `<div class="progress-bar-mobile-style"><div class="progress-bar-mobile-left"><div style="height:${process_width}%;background:${config_color};"></div></div>`

        color_str = ''

        let class_name = ''
            , check_status = false

        progress_arr.forEach(v => {

            // Check if the status is exceeded
            check_status = check_status_process(status_number, v.status)

            color_str = check_status ? `background:${config_color};` : ''

            class_name = 'progress-bar-mobile-list'

            if (!v.time) {
                class_name = `progress-bar-mobile-list ${check_status ? 'progress-bar-mobile-enabled' : 'progress-bar-mobile-disabled'}`
            }

            progress_time = formatTime(v.time * 1000, pp_track_config?.date_and_time_format?.date_format ?? 0, pp_track_config?.date_and_time_format?.time_format ?? 0, {
                is_hidden_year: true,
                is_hidden_time: true,
                has_second: false
            })

            str += `<div class="${class_name}"><span class="progress-bar-mobile-node" style="${color_str}"></span>`

            str += v.msvg || v.svg

            str += `<div class="progress-bar-mobile-content"><b>${v.name}</b>`

            if (v.time) {
                str += `<span>${progress_time}</span>`
            }

            str += '</div></div>'
        })

        str += '</div>'

        return [str, process_width]
    }

    function get_process_width(over_width, status_number, every_left, node_count) {

        const MAX_WIDTH = 100

        status_number = parseInt(status_number)

        switch (status_number) {
            case 1001 :
                return 3
            case 1002 :
                return MAX_WIDTH - ((3 + node_count) * every_left) + over_width
            case 1003 :
                return MAX_WIDTH - ((2 + node_count) * every_left) + over_width
            case 1004 :
                return MAX_WIDTH - (4 * every_left) + over_width
            case 1100 :
                return MAX_WIDTH - (3 * every_left) + over_width
            case 3 :
                return MAX_WIDTH - every_left + over_width
            case 4 :
                return MAX_WIDTH
            default :
                return MAX_WIDTH - (2 * every_left) + over_width
        }
    }

    // Check the current status position
    function check_status_process(status_number, status) {

        if (status_number > 1000) {
            // If the current state is between custom states

            // Exceeded current status
            if (status_number < status) return false

            // Other status
            if (status < 1000) return false

        } else {
            // Non-custom status

            status_number = status_number > 4 ? 2 : status_number

            if (status < 1000 && status_number < status) return false
        }

        return true
    }


    function init_track_info(track_info_list) {

        const $tracking_result = $pp_tracking_section.children('.tracking-result')

        track_info_list.forEach((v, i) => {
            if (v.trackinfo?.length > 0) {
                let str = ''

                v.trackinfo.forEach(track => {
                    const track_date = track.date
                        , track_event = track.status_description
                        , track_local = track.details
                        , track_status = track.checkpoint_status
                        , status_img = (STATUS_CONFIG[track_status] || STATUS_CONFIG.blank).img

                    str += track_item(status_img, track_date, track_event, track_local)
                })

                $tracking_result.eq(i).find('ul.pp_tracking_result_parent').html(str)
            }
        })
    }


    function track_item(status_img, track_date, track_event, track_local) {

        if (track_local && track_event !== track_local) {
            track_event += `, ${track_local}`
        }

        track_event = track_event.trim()

        if (!track_event) {
            return ''
        }

        var pattern = /[0-9]\d*/g, check_date, check_length = 0;
        if (track_date.length === 10) {
            check_date = track_date.match(pattern);
            if (check_date && check_date[0]) {
                check_length = check_date[0].length
            }
        }

        if (track_date.length === 10 && check_length === 10) {
            track_date = formatTime(track_date * 1000, pp_track_config?.date_and_time_format?.date_format ?? 0, pp_track_config?.date_and_time_format?.time_format ?? 0, {
                is_hidden_year: false,
                is_hidden_time: false,
                has_second: false
            })
        }

        return `<li><div class="timeline-item"><div class="timeline-badge"><img class="timeline-badge-userpic" src="${status_img}"></div><div class="timeline-body"><div class="timeline-body-arrow"></div><div class="timeline-body-head"><div class="timeline-body-head-caption"><span>${track_date}</span></div><div class="timeline-body-head-actions"></div></div><div class="timeline-body-content"><span class="font-grey-cascade">${track_event}</span></div></div></div></li>`
    }

    function get_progress_icon_url(icon) {
        return `${ASSETS_PATH}icons/progress/${icon}.svg`
    }

    function get_status_icon_url(icon) {
        return `${ASSETS_PATH}icons/status/${icon}.png`
    }

    function get_shipment_status_icon_url_2(name) {
        return `https://cdn.parcelpanel.com/assets/shipment_status_icon/202205/${name}.svg?_v=4`
    }


    /**
     * email do change
     */
    function encode_email(email) {
        if (-1 !== email.indexOf('@')) {
            const reverse = () => {
                let str = ''
                    , i = email.length
                for (; i >= 0; i--) {
                    str += email.charAt(i)
                }
                return str
            }

            return reverse(email).replace('@', '_-_')
        }

        return email
    }


    /**
     * 初始化谷歌翻译插件
     */
    function init_google_translate_element() {

        new google.translate.TranslateElement({
            pageLanguage: 'en',
            layout: google.translate.TranslateElement.FloatPosition.BOTTOM_RIGHT,
            multilanguagePage: true
        }, 'pp-google-translate-element')

        init_ok('google_translate')
    }

    // 任务初始化后触发
    function init_ok(name) {
        // console.log(`${name} ok.`)
        initialized_tasks[name] = true
        after_call()
    }

    function after_call() {
        if (initialized_tasks['google_translate'] && initialized_tasks['tracking_result']) {
            // Tracking result 和 Google translate 初始化后执行操作
            const i = $order_nums.children('.active').data('id') || 0
            // 显示谷歌翻译插件
            display_translate_element(i)
        }
    }

    /**
     * 判断空对象
     */
    function is_empty_object(obj) {
        return Object.getOwnPropertyNames(obj).length === 0
    }

    // 暴露接口
    if ('object' !== typeof window.pp_functions) {
        window.pp_functions = {}
    }

    window.pp_functions.init_google_translate_element = init_google_translate_element

})(jQuery)

/**
 * 谷歌翻译接口初始化回调函数
 */
function pp_init_google_translate_element() {
    window.pp_functions?.init_google_translate_element()
}

/**
 * 谷歌地图iframe加载完成的回调函数
 * @param index
 */
function pp_google_map_loaded(index) {
    const map_obj = document.querySelector(`#pp-map${index}`)
    map_obj && (map_obj.style.display = 'block')
}


function formatTime(
    time,
    dateValue,
    timeValue,
    {is_hidden_year, is_hidden_time, has_second} = {}
) {
    const date2format = {
        0: "MMM DD, YYYY",
        1: "MMM DD",
        2: "MMM Do YYYY",
        3: "MM/DD/YYYY",
        4: "DD/MM/YYYY",
        5: "DD.MM.YYYY",
        7: "DD MMM YYYY",
        8: "DD-MMM-YYYY",
    };
    const time2format = {0: "hh:mm a", 1: "HH:mm"};
    let format = `${date2format[dateValue]}`;
    if (!is_hidden_time) {
        format += ` ${time2format[timeValue]}`;
        if (has_second) {
            format = format.replace(":mm", ":mm:ss");
        }
    }
    if (is_hidden_year) {
        format = format.replace(/[^A-Z]+YYYY/, "");
    }
    return dayjs(time).format(format);
}

function convertStyle(style) {
    return Object.entries(style).reduce((str, [attr, value]) => `${str}${attr}: ${value};`, '')
}
