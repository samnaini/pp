jQuery(($) => {
  new PPVue({
    el: '#pp-app',
    data: function () {
      return {
        basename: '', // 路由路径

        parcelpanel: parcelpanel_param,
        pageParam: pp_param,
        checked: false,
        isOpenModal: false, // 模态框
        isDropshippingModeBtnLoading: false,

        showPostLayer: true,  // Post 是否显示

        route: {
          fullPath: '',
          hash: '',
          query: {},
          path: '',
          href: '',
        },
      }
    },
    created() {
      // window.onbeforeunload = () => {
      //   if (this.showProgress) {
      //     return true
      //   }
      // }

      /* 调用解析自定义路由 */
      this.parseRoute()

      window.onhashchange = () => {
        this.parseRoute()
      }
    },
    mounted() {
      // this.getHistoryUploadRecords()
      if (pp_auth_key && !pp_is_empty_api_kay) this.bindWithParcelPanel()
    },
    methods: {
      /** 解析路由 */
      parseRoute() {
        const href = window.location.hash
        const fullPath = href.replace(/^#/, '')
        let tempPath = fullPath

        const hashIndexOf = tempPath.indexOf('#')
        if (hashIndexOf !== -1) {
          this.route.hash = tempPath.substring(hashIndexOf)
          tempPath = tempPath.substring(0, hashIndexOf)
        }

        const queryIndexOf = tempPath.indexOf('?')
        if (queryIndexOf !== -1) {
          // todo 解析 query
          // const query = tempPath.substring(queryIndexOf)
          tempPath = tempPath.substring(0, queryIndexOf)
        }

        const path = tempPath

        this.route = {
          ...this.route,
          fullPath,
          path,
          href,
        }
      },
      // 绑定账号
      bindWithParcelPanel() {
        $.ajax({
          type: 'POST',
          url: `${ ajaxurl }?action=pp_bind&_ajax_nonce=${ pp_bind_account }`,
          data: {
            auth_key: pp_auth_key,
          },
          success: res => {
            if (res.success !== true) {
              $.toastr.warning(res.msg || 'Failed to connect.')
              return
            }

            $.toastr.success(res.msg || 'Connected successfully.')

            // 如果含有跳转的链接，就进行跳转
            if (res.redirect) {
              window.history.replaceState({}, document.title, res.redirect)
            }
          },
        })
      },

      // open or close modal
      modal: function () {
        // this.isOpenModal = !this.isOpenModal
        window.location.hash = '#/import-tracking'
      },

      previewTrackPage() {
        window.open(this.pageParam.preview_track_link)
      },

      jumpToSettingsPage() {
        location.href = this.pageParam.settings_page_link
      },

      onClickEnableDropshippingMode() {
        this.isDropshippingModeBtnLoading = true
        $.ajax({
          type: 'POST',
          url: `${ ajaxurl }?action=pp_enable_dropshipping_mode&_ajax_nonce=${ pp_enable_dropshipping_nonce }`,
          complete: () => {
            this.isDropshippingModeBtnLoading = false
          },
          success: res => {
            $.toastr.success(res.msg)
          },
          error() {
            $.toastr.error('Server error, please try again or refresh the page')
          },
        })
      },

      // 联系我们
      onContactUs() {
        // 唤起Intercom
        window.PPLiveChat('showNewMessage', 'Hi support, my tracking page looks not so good, can you check it for me?')
      },

      // 隐藏 or 显示 details 页面
      isShowDetailView: function (val) {
        this.isShowDetails = !this.isShowDetails
        if (this.isShowDetails) {
          this.Details = val
        }
      },

      handlePostLayerShow(show) {
        this.showPostLayer = show
      },
    },
  })
})
