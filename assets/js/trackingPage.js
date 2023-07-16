jQuery(($) => {
  const option = {
    tabSize: 2, // tab大小
    mode: 'text/css', // 编辑器模式支持文件
    theme: 'material-darker', // 编辑器主题
    lineNumbers: true, // 编辑器行号
    line: true,
    dragDrop: true, // 拖拽
    lineWrapping: true, // 代码折叠
    matchBrackets: true, // 括号匹配
    indentWithTabs: true, // 首行缩进
    smartIndent: true,
    extraKeys: {'Ctrl': 'autocomplete'}, // ctrl唤起智能提示
    hintOptions: {
      tables: {
        users: ['name', 'score', 'birthDate'],
        countries: ['name', 'population', 'size'],
      },
    },
  }

  const dateFormat = [
    {label: 'Dec 31, 2018', value: '0'},
    {label: 'Dec 31', value: '1'},
    {label: 'Dec 31st 2018', value: '2'},
    {label: '31 Dec 2018', value: '7'},
    {label: '31-Dec-2018', value: '8'},
    {label: '12/31/2018 (m/d/yyyy)', value: '3'},
    {label: '31/12/2018 (d/m/yyyy)', value: '4'},
    {label: '31.12.2018 (d.m.yyyy)', value: '5'},
  ]

  const timeFormat = [
    {label: '08:42 am', value: '0'},
    {label: '24-hour time', value: '1'},
  ]

  const langList = [
    {label: 'Chinese', value: 'zh-Hans'},
    {label: 'Dutch', value: 'nl'},
    {label: 'English', value: 'en'},
    {label: 'French', value: 'fr'},
    {label: 'German', value: 'de'},
    {label: 'Italian', value: 'it'},
    {label: 'Spanish', value: 'es'},
  ]

  const EDTCalculateFromOptions = [
    {
      id: 0,
      value: 'Order created time',
    },
    {
      id: 1,
      value: 'Order fulfilled time',
    },
  ]
  const countryList = [
    {label: 'Afghanistan', value: 'AF'},
    {label: 'Åland Islands', value: 'AX'},
    {label: 'Albania', value: 'AL'},
    {label: 'Algeria', value: 'DZ'},
    {label: 'Andorra', value: 'AD'},
    {label: 'Angola', value: 'AO'},
    {label: 'Anguilla', value: 'AI'},
    {label: 'Antigua Barbuda', value: 'AG'},
    {label: 'Argentina', value: 'AR'},
    {label: 'Armenia', value: 'AM'},
    {label: 'Aruba', value: 'AW'},
    {label: 'Australia', value: 'AU'},
    {label: 'Austria', value: 'AT'},
    {label: 'Azerbaijan', value: 'AZ'},
    {label: 'Bahamas', value: 'BS'},
    {label: 'Bahrain', value: 'BH'},
    {label: 'Bangladesh', value: 'BD'},
    {label: 'Barbados', value: 'BB'},
    {label: 'Belarus', value: 'BY'},
    {label: 'Belgium', value: 'BE'},
    {label: 'Belize', value: 'BZ'},
    {label: 'Benin', value: 'BJ'},
    {label: 'Bermuda', value: 'BM'},
    {label: 'Bhutan', value: 'BT'},
    {label: 'Bolivia', value: 'BO'},
    {label: 'Bosnia Herzegovina', value: 'BA'},
    {label: 'Botswana', value: 'BW'},
    {label: 'Bouvet Island', value: 'BV'},
    {label: 'Brazil', value: 'BR'},
    {label: 'British Indian Ocean Territory', value: 'IO'},
    {label: 'British Virgin Islands', value: 'VG'},
    {label: 'Brunei', value: 'BN'},
    {label: 'Bulgaria', value: 'BG'},
    {label: 'Burkina Faso', value: 'BF'},
    {label: 'Burundi', value: 'BI'},
    {label: 'Cambodia', value: 'KH'},
    {label: 'Cameroon', value: 'CM'},
    {label: 'Canada', value: 'CA'},
    {label: 'Cape Verde', value: 'CV'},
    {label: 'Caribbean Netherlands', value: 'BQ'},
    {label: 'Cayman Islands', value: 'KY'},
    {label: 'Central African Republic', value: 'CF'},
    {label: 'Chad', value: 'TD'},
    {label: 'Chile', value: 'CL'},
    {label: 'China', value: 'CN'},
    {label: 'Christmas Island', value: 'CX'},
    {label: 'Cocos (Keeling) Islands', value: 'CC'},
    {label: 'Colombia', value: 'CO'},
    {label: 'Comoros', value: 'KM'},
    {label: 'Congo - Brazzaville', value: 'CG'},
    {label: 'Congo - Kinshasa', value: 'CD'},
    {label: 'Cook Islands', value: 'CK'},
    {label: 'Costa Rica', value: 'CR'},
    {label: 'Croatia', value: 'HR'},
    {label: 'Cuba', value: 'CU'},
    {label: 'Curaçao', value: 'CW'},
    {label: 'Cyprus', value: 'CY'},
    {label: 'Czechia', value: 'CZ'},
    {label: 'Côte d’Ivoire', value: 'CI'},
    {label: 'Denmark', value: 'DK'},
    {label: 'Djibouti', value: 'DJ'},
    {label: 'Dominica', value: 'DM'},
    {label: 'Dominican Republic', value: 'DO'},
    {label: 'Ecuador', value: 'EC'},
    {label: 'Egypt', value: 'EG'},
    {label: 'El Salvador', value: 'SV'},
    {label: 'Equatorial Guinea', value: 'GQ'},
    {label: 'Eritrea', value: 'ER'},
    {label: 'Estonia', value: 'EE'},
    {label: 'Eswatini', value: 'SZ'},
    {label: 'Ethiopia', value: 'ET'},
    {label: 'Falkland Islands', value: 'FK'},
    {label: 'Faroe Islands', value: 'FO'},
    {label: 'Fiji', value: 'FJ'},
    {label: 'Finland', value: 'FI'},
    {label: 'France', value: 'FR'},
    {label: 'French Guiana', value: 'GF'},
    {label: 'French Polynesia', value: 'PF'},
    {label: 'French Southern Territories', value: 'TF'},
    {label: 'Gabon', value: 'GA'},
    {label: 'Gambia', value: 'GM'},
    {label: 'Georgia', value: 'GE'},
    {label: 'Germany', value: 'DE'},
    {label: 'Ghana', value: 'GH'},
    {label: 'Gibraltar', value: 'GI'},
    {label: 'Greece', value: 'GR'},
    {label: 'Greenland', value: 'GL'},
    {label: 'Grenada', value: 'GD'},
    {label: 'Guadeloupe', value: 'GP'},
    {label: 'Guatemala', value: 'GT'},
    {label: 'Guernsey', value: 'GG'},
    {label: 'Guinea', value: 'GN'},
    {label: 'Guinea-Bissau', value: 'GW'},
    {label: 'Guyana', value: 'GY'},
    {label: 'Haiti', value: 'HT'},
    {label: 'Heard McDonald Islands', value: 'HM'},
    {label: 'Honduras', value: 'HN'},
    {label: 'Hong Kong SAR China', value: 'HK'},
    {label: 'Hungary', value: 'HU'},
    {label: 'Iceland', value: 'IS'},
    {label: 'India', value: 'IN'},
    {label: 'Indonesia', value: 'ID'},
    {label: 'Iran', value: 'IR'},
    {label: 'Iraq', value: 'IQ'},
    {label: 'Ireland', value: 'IE'},
    {label: 'Isle of Man', value: 'IM'},
    {label: 'Israel', value: 'IL'},
    {label: 'Italy', value: 'IT'},
    {label: 'Jamaica', value: 'JM'},
    {label: 'Japan', value: 'JP'},
    {label: 'Jersey', value: 'JE'},
    {label: 'Jordan', value: 'JO'},
    {label: 'Kazakhstan', value: 'KZ'},
    {label: 'Kenya', value: 'KE'},
    {label: 'Kiribati', value: 'KI'},
    {label: 'Kosovo', value: 'XK'},
    {label: 'Kuwait', value: 'KW'},
    {label: 'Kyrgyzstan', value: 'KG'},
    {label: 'Laos', value: 'LA'},
    {label: 'Latvia', value: 'LV'},
    {label: 'Lebanon', value: 'LB'},
    {label: 'Lesotho', value: 'LS'},
    {label: 'Liberia', value: 'LR'},
    {label: 'Libya', value: 'LY'},
    {label: 'Liechtenstein', value: 'LI'},
    {label: 'Lithuania', value: 'LT'},
    {label: 'Luxembourg', value: 'LU'},
    {label: 'Macao SAR China', value: 'MO'},
    {label: 'Madagascar', value: 'MG'},
    {label: 'Malawi', value: 'MW'},
    {label: 'Malaysia', value: 'MY'},
    {label: 'Maldives', value: 'MV'},
    {label: 'Mali', value: 'ML'},
    {label: 'Malta', value: 'MT'},
    {label: 'Martinique', value: 'MQ'},
    {label: 'Mauritania', value: 'MR'},
    {label: 'Mauritius', value: 'MU'},
    {label: 'Mayotte', value: 'YT'},
    {label: 'Mexico', value: 'MX'},
    {label: 'Moldova', value: 'MD'},
    {label: 'Monaco', value: 'MC'},
    {label: 'Mongolia', value: 'MN'},
    {label: 'Montenegro', value: 'ME'},
    {label: 'Montserrat', value: 'MS'},
    {label: 'Morocco', value: 'MA'},
    {label: 'Mozambique', value: 'MZ'},
    {label: 'Myanmar (Burma)', value: 'MM'},
    {label: 'Namibia', value: 'NA'},
    {label: 'Nauru', value: 'NR'},
    {label: 'Nepal', value: 'NP'},
    {label: 'Netherlands', value: 'NL'},
    {label: 'Netherlands Antilles', value: 'AN'},
    {label: 'New Caledonia', value: 'NC'},
    {label: 'New Zealand', value: 'NZ'},
    {label: 'Nicaragua', value: 'NI'},
    {label: 'Niger', value: 'NE'},
    {label: 'Nigeria', value: 'NG'},
    {label: 'Niue', value: 'NU'},
    {label: 'Norfolk Island', value: 'NF'},
    {label: 'North Korea', value: 'KP'},
    {label: 'North Macedonia', value: 'MK'},
    {label: 'Norway', value: 'NO'},
    {label: 'Oman', value: 'OM'},
    {label: 'Pakistan', value: 'PK'},
    {label: 'Palestinian Territories', value: 'PS'},
    {label: 'Panama', value: 'PA'},
    {label: 'Papua New Guinea', value: 'PG'},
    {label: 'Paraguay', value: 'PY'},
    {label: 'Peru', value: 'PE'},
    {label: 'Philippines', value: 'PH'},
    {label: 'Pitcairn Islands', value: 'PN'},
    {label: 'Poland', value: 'PL'},
    {label: 'Portugal', value: 'PT'},
    {label: 'Qatar', value: 'QA'},
    {label: 'Réunion', value: 'RE'},
    {label: 'Romania', value: 'RO'},
    {label: 'Russia', value: 'RU'},
    {label: 'Rwanda', value: 'RW'},
    {label: 'Samoa', value: 'WS'},
    {label: 'San Marino', value: 'SM'},
    {label: 'São Tomé Príncipe', value: 'ST'},
    {label: 'Saudi Arabia', value: 'SA'},
    {label: 'Senegal', value: 'SN'},
    {label: 'Serbia', value: 'RS'},
    {label: 'Seychelles', value: 'SC'},
    {label: 'Sierra Leone', value: 'SL'},
    {label: 'Singapore', value: 'SG'},
    {label: 'Sint Maarten', value: 'SX'},
    {label: 'Slovakia', value: 'SK'},
    {label: 'Slovenia', value: 'SI'},
    {label: 'Solomon Islands', value: 'SB'},
    {label: 'Somalia', value: 'SO'},
    {label: 'South Africa', value: 'ZA'},
    {label: 'South Georgia South Sandwich Islands', value: 'GS'},
    {label: 'South Korea', value: 'KR'},
    {label: 'South Sudan', value: 'SS'},
    {label: 'Spain', value: 'ES'},
    {label: 'Sri Lanka', value: 'LK'},
    {label: 'St. Barthélemy', value: 'BL'},
    {label: 'St. Helena', value: 'SH'},
    {label: 'St. Kitts Nevis', value: 'KN'},
    {label: 'St. Lucia', value: 'LC'},
    {label: 'St. Martin', value: 'MF'},
    {label: 'St. Pierre Miquelon', value: 'PM'},
    {label: 'St. Vincent Grenadines', value: 'VC'},
    {label: 'Sudan', value: 'SD'},
    {label: 'Suriname', value: 'SR'},
    {label: 'Svalbard Jan Mayen', value: 'SJ'},
    {label: 'Sweden', value: 'SE'},
    {label: 'Switzerland', value: 'CH'},
    {label: 'Syria', value: 'SY'},
    {label: 'Taiwan', value: 'TW'},
    {label: 'Tajikistan', value: 'TJ'},
    {label: 'Tanzania', value: 'TZ'},
    {label: 'Thailand', value: 'TH'},
    {label: 'Timor-Leste', value: 'TL'},
    {label: 'Togo', value: 'TG'},
    {label: 'Tokelau', value: 'TK'},
    {label: 'Tonga', value: 'TO'},
    {label: 'Trinidad Tobago', value: 'TT'},
    {label: 'Tunisia', value: 'TN'},
    {label: 'Turkey', value: 'TR'},
    {label: 'Turkmenistan', value: 'TM'},
    {label: 'Turks Caicos Islands', value: 'TC'},
    {label: 'Tuvalu', value: 'TV'},
    {label: 'U.S. Outlying Islands', value: 'UM'},
    {label: 'Uganda', value: 'UG'},
    {label: 'Ukraine', value: 'UA'},
    {label: 'United Arab Emirates', value: 'AE'},
    {label: 'United Kingdom', value: 'GB'},
    {label: 'United States', value: 'US'},
    {label: 'Uruguay', value: 'UY'},
    {label: 'Uzbekistan', value: 'UZ'},
    {label: 'Vanuatu', value: 'VU'},
    {label: 'Vatican City', value: 'VA'},
    {label: 'Venezuela', value: 'VE'},
    {label: 'Vietnam', value: 'VN'},
    {label: 'Wallis Futuna', value: 'WF'},
    {label: 'Western Sahara', value: 'EH'},
    {label: 'Yemen', value: 'YE'},
    {label: 'Zambia', value: 'ZM'},
    {label: 'Zimbabwe', value: 'ZW'},
  ]

  const prodRecommendationDisplayPositionOptions = [
    {
      id: 0,
      value: 'Top',
    },
    {
      id: 2,
      value: 'Right',
    },
    {
      id: 1,
      value: 'Bottom',
    },
  ]
  // const productCategories = pp_tracking_page_settings.product_categories.map(v => ({
  //   value: v.term_id,
  //   label: v.name,
  // }))

  function _deep(list, res = [], pid = 0, deep = 0) {
    list.forEach(v => {
      if (v.parent === pid) {
        res.push({
          value: v.term_id,
          label: `${ '　'.repeat(deep) }${ v.name }`
        })
        _deep(list, res, v.term_id, deep + 1)
      }
    })
  }

  const trackingPage = new PPVue({
    el: '#pp-app',
    data: {
      parcelpanel: parcelpanel_param,
      countryList,
      EDTCalculateFromOptions,
      prodRecommendationDisplayPositionOptions,
      productCategories: [],
      codeMirrorOption: option,
      isOpenModal: false,
      sendingApi: false,
      // 数据
      settings: pp_tracking_page_settings || {}, // 此页面所有数据
      containerWidth: 1200, // Theme container width
      widthType: 'px', // Theme container width type
      radioGroups: [ // Theme container width type
        {
          id: 0,
          value: '%',
        },
        {
          id: 1,
          value: 'px',
        },
      ],
      barColor: '#008000', // Progress bar color
      UIStylesList: [ // UI styles
        {
          index: 0,
          id: 'UIStyles_0',
          value: 'Light style',
        },
        {
          index: 1,
          id: 'UIStyles_1',
          value: 'Dark style',
        },
      ],
      mapCoordinatesPosition: [ // map_coordinates position
        {
          index: 0,
          id: 'position_0',
          value: 'Current location',
        },
        {
          index: 1,
          id: 'position_1',
          value: 'Destination address',
        },
      ],
      keywords: '',
      // ---------------------------------
      // Tracking page translations
      trackingPageTranslationsDefault: {},
      // ---------------------------------
      // Custom order status
      isEmpty: true,
      editIndex: -1,
      status: '',
      trackingInfo: '',
      days: '',
      customStatusData: [],
      // ---------------------------------
      // Date and time format
      dateTimeFormat1Default: '',
      dateTimeFormat2Default: '',
      dateTimeFormat1: dateFormat,
      dateTimeFormat2: timeFormat,
      // ---------------------------------
      // EDT
      edtProcessStyle: {
        'background-color': 'var(--wp-admin-theme-color)',
      },
      edtTooltipStyle: {
        'background-color': 'rgba(0,0,0,.75)',
        'border-width': '0',
        'border-color': 'rgba(0,0,0,.75)',
      },
      // ---------------------------------
      // Additional text setting
      textAbove: '',
      textBelow: '',

      // ---------------------------------
      // manually translate tracking detailed info
      trackingDetailedInfoList: [
        {'before': '', 'after': ''},
      ],

      // ---------------------------------
      // Theme language
      langList,

      customCode: {
        css: '',
        htmlTop: '',
        htmlBottom: '',
      },
    },
    created() {
      this.countryList.unshift({label: 'Country/Region', value: ''})

      const res = []
      _deep(pp_tracking_page_settings.product_categories, res)
      this.productCategories = res
      this.productCategories.unshift({label: 'Product Category', value: '0'})
    },
    mounted() {
      this.init()
    },
    methods: {

      /**
       * 初始化
       */
      init() {
        this.widthType = this.settings.display_option.width_unit
        this.barColor = this.settings.display_option.color
        this.containerWidth = this.settings.display_option.width
        this.keywords = this.settings.display_option.hide_keywords

        this.trackingPageTranslationsDefault = this.settings.tracking_page_translations_default
        delete this.settings.tracking_page_translations_default

        this.customStatusData = this.settings.custom_order_status
        this.checkIsEmpty()
        this.textAbove = this.settings.additional_text_setting.text_above
        this.textBelow = this.settings.additional_text_setting.text_below
        this.dateTimeFormat1Default = this.settings.date_and_time_format.date_format
        this.dateTimeFormat2Default = this.settings.date_and_time_format.time_format
        if (this.settings.translate_tracking_detailed_info.length) {
          this.trackingDetailedInfoList = this.settings.translate_tracking_detailed_info
        }

        const IGNORE_TRANSLATION_FIELDS = ['transit', 'pickup', 'undelivered']

        IGNORE_TRANSLATION_FIELDS.forEach(v => {
          delete this.settings.tracking_page_translations[v]
        })

        const {
          css,
          html_top: htmlTop,
          html_bottom: htmlBottom
        } = this.settings.custom_css_and_html
        this.customCode = {css, htmlTop, htmlBottom}
        delete this.settings.custom_css_and_html
      },

      // open modal
      openModal() {
        this.reDefault()
        this.isOpenModal = true
        // console.log(this.textAbove);
      },

      // close modal 
      closeModal() {
        this.isOpenModal = false
        this.isEdit = false
      },

      previewTrackPage() {
        window.open(this.settings.trackurl)
      },

      // Theme container width
      widthValueChange(val) {
        this.containerWidth = val
        this.settings.display_option.width = val
      },

      // px 或者 %
      changeWidthType(val) {
        this.widthType = val
        this.settings.display_option.width_unit = val

        if ('%' === val) {
          this.widthValueChange(100)
        } else {
          this.widthValueChange(1200)
        }
      },

      // bar Color change
      colorValueChange(val) {
        this.barColor = val
        this.settings.display_option.color = val
      },

      // UI styles
      getRadioVal(val) {
        if (this.UIStylesList[0].id == val) this.settings.display_option.ui_style = 0
        if (this.UIStylesList[1].id == val) this.settings.display_option.ui_style = 1

      },

      // Map Coordinates Position
      getMapCoordinatesPosition(val) {
        if (this.mapCoordinatesPosition[0].id == val) this.settings.display_option.map_coordinates_position = 0
        if (this.mapCoordinatesPosition[1].id == val) this.settings.display_option.map_coordinates_position = 1
      },

      // Hide keywords
      getKeywords(val) {
        this.keywords = val
        this.settings.display_option.hide_keywords = val
      },
      // ---------------------------------

      // Tracking page translations
      // 卡片栏值改变时，实时改变对象值
      // 在input中已实现 settings值改变
      // ---------------------------------

      // Custom order status
      saveCustomStatus_status(val) {
        this.status = val
        // console.log(this.status);
      },

      saveCustomStatus_trackingInfo(val) {
        this.trackingInfo = val
        // console.log(this.trackingInfo);
      },

      saveCustomStatus_days(val) {
        this.days = val
        // console.log(this.days);
      },
      reDefault() {
        this.saveCustomStatus_status('Custom Status Name')
        this.saveCustomStatus_trackingInfo('')
        this.saveCustomStatus_days('')
        this.editIndex = -1
      },

      inputCustomStatus() { // modal输入数据
        if (this.editIndex >= 0) { // 更新数据
          let data = this.customStatusData[this.editIndex]
          data.status = this.status
          data.info = this.trackingInfo
          data.days = this.days
          this.editIndex = -1
          this.isOpenModal = false
          this.settings.custom_order_status = this.customStatusData
          return
        }
        if (this.customStatusData.length >= 3) {
          $.toastr.warning('Up to 3')
          return
        }
        let data = {
          'status': this.status || 'Custom Status Name',
          'info': this.trackingInfo || '',
          'days': this.days || '0',
        }
        this.customStatusData.push(data)
        this.settings.custom_order_status = this.customStatusData
        this.isOpenModal = false
        this.isEmpty = false
      },

      deleteCustomStatusData(index) { // 删除数据
        this.customStatusData.splice(index, 1)
        this.settings.custom_order_status = this.customStatusData
        // console.log(this.settings);
        this.checkIsEmpty()
      },

      updateCustomStatusData(index) { // 编辑数据
        let data = this.customStatusData[index]
        this.openModal()
        this.saveCustomStatus_status(data.status)
        this.saveCustomStatus_trackingInfo(data.info)
        this.saveCustomStatus_days(data.days)
        this.editIndex = index
      },

      checkIsEmpty() { // 判断是否为空值
        if (this.customStatusData.length == 0) {
          this.isEmpty = true
        } else {
          this.isEmpty = false
        }
      },


      // ---------------------------------
      // estimated delivery time
      /**
       * 处理复选框变更事件
       * @param enabled
       */
      handleEDTBODEnabledCheckboxChange(enabled) {
        if (enabled) {
          this.initEDTBODItems()
        } else {
          this.filterEDTBODItems()
        }
      },

      /**
       * 处理删除按钮的点击事件
       * @param index
       */
      handleEDTBODItemRemoveClick(index) {
        this.settings.estimated_delivery_time.bod_items.splice(index, 1)
      },

      /**
       * 处理添加按钮点击事件
       */
      handleEDTBODItemAddClick() {
        this.addEDTBODItem()
      },

      /**
       * 初始化选项列表
       */
      initEDTBODItems() {
        const items = this.settings.estimated_delivery_time.bod_items
        if (!items.length) {
          this.addEDTBODItem()
        }
      },

      /**
       * 过滤未选择的 Shipping to 的选项
       */
      filterEDTBODItems() {
        const uniqToSet = new Set()
        this.settings.estimated_delivery_time.bod_items = this.settings.estimated_delivery_time.bod_items
          .filter(v => v.to)
          .filter(v => !uniqToSet.has(v.to) && uniqToSet.add(v.to))
      },

      /**
       * 添加一个选项
       */
      addEDTBODItem(to = '', edt = [10, 20]) {
        this.settings.estimated_delivery_time.bod_items.push({to, edt})
      },


      // ---------------------------------
      // Additional text setting
      textAboveValue(newVal) {
        this.textAbove = newVal
        this.settings.additional_text_setting.text_above = this.textAbove
      },

      textBelowValue(newVal) {
        this.textBelow = newVal
        this.settings.additional_text_setting.text_below = this.textBelow
      },


      // ---------------------------------
      // Date and time format
      dateChange(newVal) {
        this.dateTimeFormat1Default = newVal
        this.settings.date_and_time_format.date_format = this.dateTimeFormat1Default
        // console.log(this.settings);
      },

      timeChange(newVal) {
        this.dateTimeFormat2Default = newVal
        this.settings.date_and_time_format.time_format = this.dateTimeFormat2Default
      },


      // ---------------------------------
      // manually translate tracking detailed info
      addManually() {
        const data = {before: '', after: ''}
        this.trackingDetailedInfoList.push(data)
      },

      deleteManually(index) {
        if (this.trackingDetailedInfoList.length <= 1) {
          this.trackingDetailedInfoList = [{before: '', after: ''}]
          return
        }
        this.trackingDetailedInfoList.splice(index, 1)
      },

      // ---------------------------------
      // Theme language
      themeLangValChange(v) {
        this.settings.theme_language.val = v

        const trans = 'en' === v ? this.trackingPageTranslationsDefault : window.PP_TRACK_PAGE_TRANS_LIST[v]

        if (trans) {
          Object.assign(this.settings.tracking_page_translations, trans)
        }
      },

      // save change button
      saveData() {
        this.sendingApi = true

        this.settings.translate_tracking_detailed_info = this.trackingDetailedInfoList

        // 过滤空的或重复的项
        this.filterEDTBODItems()
        if (!this.settings.estimated_delivery_time.bod_items.length) {
          this.settings.estimated_delivery_time.bod_enabled = false
        }

        const param = $.param({
          action: 'pp_tracking_page_save',
          _ajax_nonce: pp_tracking_page_save_nonce,
        })

        $.ajax({
          type: 'POST',
          url: `${ ajaxurl }?${ param }`,
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify(this.settings),
          complete: () => {
            this.sendingApi = false
          },
          success: res => {
            if (res.success) {
              $.toastr.success(res.msg)
            } else {
              $.toastr.error(res.msg)
            }
          },
          error() {
            $.toastr.error('Server error, please try again or refresh the page')
          },
        })
      },

      onContactUs() {
        window.PPLiveChat('show')
      },
    },
  })
})
