jQuery($ => {
  const $body = $(document.body)

  // 选中复选框后显示状态下拉框及提交按钮
  const checkbox_checked_handler = debounce(function () {
    const $this = $(this)
      , $table = $this.closest('table')

    const selected_num = $table.children('tbody').filter(':visible').children().children('.check-column').find(':checkbox:checked').length
    if (selected_num) {
      $('#box-update-status').show()
    } else {
      $('#box-update-status').hide()
    }
  }, 20)
  $body.on('click', 'table.pp-shipments .check-column :checkbox', checkbox_checked_handler)

  new PPVue({
    el: '#pp-app',
    data() {
      return {
        parcelpanel: parcelpanel_param,
        pageParam: pp_param,
        checked: false,
        isOpenExportModal: false,
        isOpenModal: false, // 模态框
        isExporting: false,  // 导出中
        totalShipments: total_items,
        // downloadLink: '',
        exportStep: 1,
        exportPercentage: 0,
        exportFileName: '',

        showPostLayer: false,  // Post 是否显示
        postLayerShowTimer: null,  // Post 显示计时器

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
      window.onbeforeunload = () => {
        if (this.isExporting) {
          return true
        }
      }

      this.postLayerShowTimer = setTimeout(() => {
        this.showPostLayer = true
      }, 1e4)

      /* 调用解析自定义路由 */
      this.parseRoute()

      window.onhashchange = () => {
        this.parseRoute()
      }
    },
    mounted() {
      $('#re-sync-submit').on('click', () => {
        this.onResync()
        return false
      })
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

        // afterEach
        if (path === '/import-tracking') {
          document.querySelector('#screen-meta-links').style.display = 'none'
        } else {
          document.querySelector('#screen-meta-links').style.display = 'block'
        }
      },
      // 导出 shipments
      onExportShipments() {
        if (this.isExporting) {
          return
        }

        this.isExporting = true


        // generate export filename
        const currentDate = new Date(),
          day = currentDate.getDate(),
          month = currentDate.getMonth() + 1,
          year = currentDate.getFullYear(),
          timestamp = currentDate.getTime()

        this.exportFileName = `parcelpanel-export-shipments-${ day }-${ month }-${ year }-${ timestamp }.csv`


        this.exportStep = 1
        this.exportPercentage = 0

        this.processExport()
      },

      processExport() {
        $.ajax({
          type: 'POST',
          url: ajaxurl + location.search,
          data: {
            action: 'pp_export_csv',
            _ajax_nonce: this.pageParam.export_nonce,
            filename: this.exportFileName,
            step: this.exportStep,
          },
          success: res => {
            if (false === res.success) {
              this.isExporting = false
              $.toastr.error(res.msg || 'Upload failed! Unknown error')
              return
            }

            this.exportStep = res.data.step
            this.exportPercentage = res.data.percentage

            if (100 === res.data.percentage) {
              setTimeout(() => {
                this.isExporting = false
                this.isOpenExportModal = false

                this.$nextTick(() => {
                  window.location = res.data.download_link
                })
              }, 1000)

              return
            }

            setTimeout(() => {
              this.processExport()
            }, 500)
          },
          error: () => {
            this.isExporting = false
            $.toastr.error('Server error, please try again or refresh the page')
          },
        })
      },

      onResync(e) {

        const $resyncTime = $('#resync-time')
        const $submitResync = $('#re-sync-submit')

        const sync = $resyncTime.val()

        if (!sync) {
          $.toastr.error('Please select sync time.')
          return
        }

        $resyncTime.attr('disabled', 'disabled')
        $submitResync.attr('disabled', 'disabled')

        $.ajax({
          type: 'POST',
          url: ajaxurl,
          data: {
            action: 'pp_resync',
            _ajax_nonce: this.pageParam.resync_nonce,
            sync,
          },
          success: res => {
            $resyncTime.removeAttr('disabled')
            $submitResync.removeAttr('disabled')
            if (res.success) {
              $.toastr.success(res.msg)
            } else {
              $.toastr.error(res.msg)
            }
          },
          error: () => {
            $.toastr.error('Server error, please try again or refresh the page')
          },
        })
      },

      handlePostLayerShow(show) {
        this.postLayerShowTimer && clearTimeout(this.postLayerShowTimer)
        this.showPostLayer = show
      },

      handleImportTrackingNumberButtonClick() {
        window.location.hash = '#/import-tracking'
      },
    },
  })

  // 手动更改订单状态提交按钮点击事件监听
  $('#update-status-submit').on('click', () => {
    const enable_form = () => {
      $('table.pp-shipments .check-column :checkbox').removeAttr('disabled')
      $('#select-update-status').removeAttr('disabled')
      $('#update-status-submit').removeAttr('disabled')
    }

    const disable_form = () => {
      $('table.pp-shipments .check-column :checkbox').attr('disabled', 'disabled')
      $('#select-update-status').attr('disabled', 'disabled')
      $('#update-status-submit').attr('disabled', 'disabled')
    }

    const set_loading = (is_loading = true) => {
      $('table.pp-shipments tbody .check-column :checkbox:checked').parent().parent('tr').toggleClass('loading', is_loading)
    }

    // 订单状态
    const update_status = $('#select-update-status').val()

    // 获取选中的复选框
    const shipments = []
    $('table.pp-shipments tbody .check-column :checkbox:checked').each(function () {
      shipments.push($(this).val())
    })

    if (!shipments.length || !update_status) {
      return false
    }

    // 禁用表单项
    disable_form()
    // 设置 loading 状态
    set_loading()

    const data = {
      shipments,
      update_status,
    }

    $.ajax({
      method: 'POST',
      url: `${ ajaxurl }?action=pp_change_custom_order_status&_ajax_nonce=${ pp_param.ajax_nonce }`,
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: (res) => {
        if (!res.success) {
          $.toastr.error(res.msg || 'Updated failed', {time: 3e3})
          // 启用表单项
          enable_form()
          // 取消 loading 状态
          set_loading(false)
          return
        }

        let tmr_tips
          , tmr_reload_page

        let DELAY_SHOW_TIP
        if (res.data.updated_orders.length) {
          req_send_email({updated_orders: res.data.updated_orders})
          DELAY_SHOW_TIP = 2500
        } else {
          DELAY_SHOW_TIP = 0
        }

        tmr_tips = setTimeout(() => {
          if (res.success) {
            $.toastr.success('Updated successfully')
          } else {
            $.toastr.error('Server error, please try again or refresh the page')
          }
        }, DELAY_SHOW_TIP)

        // 提示弹出 1s 后刷新页面
        tmr_reload_page = setTimeout(() => {
          // 延迟刷新页面
          window.location.reload()
        }, DELAY_SHOW_TIP + 500)
      },
      error() {
        $.toastr.error('Server error, please try again or refresh the page')
        // 启用表单项
        enable_form()
        // 取消 loading 状态
        set_loading(false)
      },
    })

    return false
  })

  function req_send_email(data) {
    $.ajax({
      method: 'POST',
      url: `${ ajaxurl }?action=pp_updated_orders_send_email&_ajax_nonce=${ pp_param.ajax_nonce }`,
      contentType: 'application/json',
      data: JSON.stringify(data),
    })
  }
})