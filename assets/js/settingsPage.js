jQuery(($) => {
  new PPVue({
    el: '#pp-app',
    data: {
      parcelpanel: parcelpanel_param,
      pageParam: pp_param,

      // 订单状态列表
      orderStatusList: [],

      couriers: pp_courier_config,
      searchKeyword: '',
      isShowDisabled: true, // true: disabled, false: enabled 的显示
      isSearching: false, // 列表搜索状态
      courierListLoading: false,
      // enabled数据
      enabledList: [],
      // disabled数据
      disabledList: [],
      showResultList: [], // 根据输入获得在字典树中搜索的结果不断更新的数据
      isSelectAll: false, // 多选框选中状态
      selectAllList: [], // 多选框选中列表  true false；
      count: 0, // 计数
      page: 0, // 分页
      pageSize: 20, // 每一页的数量
      viewNum: 0, // 视图数量

      // Tracking 部分 开关
      emailNotificationAddTrackingSection: false,

      // Tracking 部分 订单状态选中列表
      trackingSectionOrderStatus: pp_setting_config.tracking_section_order_status,

      // 运输状态邮件通知开关
      emailNotifications: pp_setting_config.email_notification,
      emailNotificationsDisabled: {
        in_transit: false,
        out_for_delivery: false,
        delivered: false,
        exception: false,
        failed_attempt: false,
        email_notification_add_tracking_section: false,
        orders_page_add_track_button: false,
        status_shipped: false,
        admin_order_actions_add_track: false,
      },

      // Track 按钮 开关
      ordersPageAddTrackButton: pp_setting_config.orders_page_add_track_button,

      // Track 按钮 订单状态选中列表
      trackButtonOrderStatus: pp_setting_config.track_button_order_status,


      // status shipped label 开关
      isStatusShipped: pp_setting_config.status_shipped,


      // Add Tracking 开关
      adminOrderActionsAddTrack: pp_setting_config.admin_order_actions_add_track,
      adminOrderActionsAddTrackOrderStatus: pp_setting_config.admin_order_actions_add_track_order_status,


      // 启用运输商列表暂存区
      tmpEnabledExpressList: [],
      // 禁用运输商列表暂存区
      tmpDisabledExpressList: [],


      // view example modal 开关
      isOpenViewExampleModalOrderTrackButton: false,
      isOpenViewExampleModalAdminOrdersAddTrackButton: false,
    },

    created() {
      const order_statuses = pp_setting_config.order_status

      // 处理 pp-select 数据格式
      for (const k in order_statuses) {
        this.orderStatusList.push({
          label: order_statuses[k],
          id: k,
        })
      }

      this.isShowDisabled = 0 === this.couriers.enable.length

      this.enabledList = this.couriers.enable
      this.disabledList = this.couriers.disable

      // 后端输出 可能是字符串
      this.emailNotificationAddTrackingSection = pp_setting_config.email_notification_add_tracking_section
      this.ordersPageAddTrackButton = pp_setting_config.orders_page_add_track_button

      // 保存 Tracking 块 - 订单状态关联
      this.changeTrackingSectionOrderStatus = debounce(this.saveTrackingSectionOrderStatus, 1000)
      // 保存 Track 按钮 - 订单状态关联
      this.changeTrackButtonOrderStatus = debounce(this.saveTrackButtonOrderStatus, 1000)
      // 保存 Add Tracking 按钮 - 订单状态关联
      this.changeAdminOrderActionsAddTrackOrderStatus = debounce(this.saveAdminOrderActionsAddTrackOrderStatus, 1000)

      // 防抖搜索
      this.changeCourierSearchInput = debounce(this.searchExpress, 200)

      // 防抖保存 Courier Matching
      this.dbncSaveCourierMatching = debounce(this.courierMatchingSave, 1000)
    },
    watch: {
      isShowDisabled(newVal) {
        this.isShowTaggle()
      },
    },
    computed: {
      listTotal() {
        if (this.isShowDisabled && !this.isSearching) {
          return this.disabledList.length
        }
        return this.showResultList.length
      },
      resultList() {
        if (this.isSearching) {
          return this.showResultList
        }

        if (!this.isShowDisabled) {
          return this.enabledList
        }

        const offset = this.page * this.pageSize

        return this.disabledList.slice(offset, offset + this.pageSize)
      },

      trackingSectionOrderStatusList() {
        return this.orderStatusList.filter(v => !['wc-pending', 'wc-on-hold', 'wc-checkout-draft'].includes(v.id))
      },

      trackButtonOrderStatusList() {
        return this.orderStatusList.filter(({id}) => !['wc-pending', 'wc-on-hold'].includes(id))
      },
    },
    methods: {

      debounce,


      /**
       * 模糊搜索对象
       * 来源于：https://juejin.cn/post/6844904113986076686
       *
       * @param {object[]} items 所有数据
       * @param {string} keyword 查询的关键词
       * @return {object[]}  输出结果
       */
      selectMatchItem(items, keyword) {
        const reg = new RegExp(keyword, 'gi')
        return items.filter(item => {
          if (reg.test(item.express) || reg.test(item.name)) {
            return true
          }
        })
      },

      searchExpress(word) {

        this.searchKeyword = word

        this.isSearching = 0 < word.trim().length

        if (!this.isSearching) {
          return
        }

        this.showResultList = this.selectMatchItem(this.isShowDisabled ? this.disabledList : this.enabledList, word)
      },

      // --------------------------------------------

      // 切换选项卡
      isShowTaggle() {
        this.page = 0
        // 情况搜索记录
        this.searchExpress('')
        this.initSelectList(this.resultList)
        this.count = 0
        this.isSelectAll = false
      },

      // 初始化选中框
      initSelectList(arr) {
        arr.forEach(v => {
          this.selectAllList[v.express] = false
        })
      },

      // 全选多选框
      enabledControlData(checked) {
        this.resultList.forEach(v => {
          this.enabledCheckboxValueChange(v.express, checked)
        })
        if (!checked) {
          this.count = 0
        } else {
          this.count = this.resultList.length
        }
      },

      // 全选多选框
      disabledControlData(checked) {
        this.resultList.forEach(v => {
          this.disabledCheckboxValueChange(v.express, checked)
        })
        if (!checked) {
          this.count = 0
        } else {
          this.count = this.resultList.length
        }
      },


      // 多选框
      enabledCheckboxValueChange(id, e) {
        if (!e) {
          this.count--
        } else {
          this.count++
        }
        this.selectAllList[id] = e
        PPVue.set(this.selectAllList, id, this.selectAllList[id])

        this.isSelectAll = true
        for (const k in this.selectAllList) {
          if (!this.selectAllList[k]) {
            this.isSelectAll = false
            break
          }
        }
      },

      // 多选框
      disabledCheckboxValueChange(id, e) {
        if (!e) {
          this.count--
        } else {
          this.count++
        }
        this.selectAllList[id] = e
        PPVue.set(this.selectAllList, id, this.selectAllList[id])

        this.isSelectAll = true
        for (const k in this.selectAllList) {
          if (!this.selectAllList[k]) {
            this.isSelectAll = false
            break
          }
        }
      },

      // 数组位置交换
      swapPos(index1, index2) {
        const exp1 = this.resultList[index1].express
        const exp2 = this.resultList[index2].express

        const enabledIndex1 = this.enabledList.findIndex(v => exp1 === v.express)
        const enabledIndex2 = this.enabledList.findIndex(v => exp2 === v.express)

        this.swapArrayData(this.enabledList, enabledIndex1, enabledIndex2)  // 交换 Enabled List

        if (this.isSearching) {
          this.swapArrayData(this.showResultList, index1, index2)  // 交换搜索结果列表
        }

        this.tmpEnabledExpressList = this.enabledList.map(v => v.express)
        this.dbncSaveCourierMatching()
      },

      swapArrayData(array, index1, index2) {
        ;[array[index1], array[index2]] = [array[index2], array[index1]]
        // 手动触发
        PPVue.set(array, index1, array[index1])
        PPVue.set(array, index2, array[index2])
      },

      // 加入快递
      enableExpress(express, save = true) {
        const index = this.disabledList.findIndex(v => express === v.express)

        if (-1 === index) {
          return false
        }

        if (this.disabledList[index] && this.count > 0) {
          this.count--
        }

        const item = this.disabledList.splice(index, 1)
        this.enabledList.push(item[0])

        // 维护选中状态列表
        delete this.selectAllList[express]

        // 特殊处理搜索状态
        if (this.isSearching) {
          const index = this.showResultList.findIndex(v => express === v.express)
          if (-1 !== index) {
            this.showResultList.splice(index, 1)

            if (!this.showResultList.length) {
              this.searchExpress('')
            }
          }
        }

        if (!this.selectAllList.length) {
          this.isSelectAll = false
        }

        this.tmpEnabledExpressList.push(express)
        save && this.courierMatchingSave()
      },

      // 多选加入快递
      enableMoreExpress() {
        let hasExpress = false
        for (const express in this.selectAllList) {
          if (this.selectAllList[express]) {
            hasExpress = true
            this.enableExpress(express, false)
          }
        }
        hasExpress && this.courierMatchingSave()
        this.count = 0
      },

      // 多选disabled快递
      disableMoreExpress() {
        const couriers = []
        for (const express in this.selectAllList) {
          if (this.selectAllList[express]) {
            couriers.push(express)

            const index = this.enabledList.findIndex(v => express === v.express)
            const item = this.enabledList.splice(index, 1)

            this.disabledList.push(item[0])

            // 维护选中状态列表
            delete this.selectAllList[express]

            // 特殊处理搜索状态
            if (this.isSearching) {
              const index = this.showResultList.findIndex(v => express === v.express)
              this.showResultList.splice(index, 1)

              if (!this.showResultList.length) {
                this.searchExpress('')
              }
            }
          }
        }
        if (!this.selectAllList.length) {
          this.isSelectAll = false
        }
        this.count = 0
        if (couriers) {
          this.tmpDisabledExpressList = [...this.tmpDisabledExpressList, ...couriers]
          this.courierMatchingSave()
        }

        // 列表重排序
        this.disabledList.sort((a, b) => {
          const aSort = parseInt(a.sort)
          const bSort = parseInt(b.sort)
          return aSort > bSort ? 1 : (aSort < bSort ? -1 : 0)
        })
      },

      // 分页
      changePages(index) {
        this.page += index
        this.viewNum = 20
      },

      // 保存 TrackingSectionOrderStatus
      saveTrackingSectionOrderStatus() {
          if (this.trackingSectionOrderStatus.length < 1) {
            this.emailNotificationAddTrackingSection = false
          }
          this.settingsSave({tracking_section_order_status: this.trackingSectionOrderStatus})
      },

      // 保存 TrackButtonOrderStatus
      saveTrackButtonOrderStatus() {
        if (this.trackButtonOrderStatus.length < 1) {
          this.ordersPageAddTrackButton = false
        }
        this.settingsSave({track_button_order_status: this.trackButtonOrderStatus})
      },

      // 保存 AdminOrderActionsAddTrackOrderStatus
      saveAdminOrderActionsAddTrackOrderStatus() {
        if (this.adminOrderActionsAddTrackOrderStatus.length < 1) {
          this.adminOrderActionsAddTrack = false
        }
        this.settingsSave({admin_order_actions_add_track_order_status: this.adminOrderActionsAddTrackOrderStatus})
      },

      // 保存 Email Notifications 开关状态
      saveEmailNotificationSwitch(n) {
        this.emailNotificationsDisabled[n] = true
        this.settingsSave({email_notification: {[n]: this.emailNotifications[n]}}, {
          complete: () => {
            this.emailNotificationsDisabled[n] = false
          },
          error: () => {
            // 失败就还原选项
            this.emailNotifications[n] = !this.emailNotifications[n]
          },
        })
      },

      // 保存 Tracking 块 开关
      saveEmailNotificationAddTrackingSection() {
        this.emailNotificationsDisabled.email_notification_add_tracking_section = true
        this.settingsSave({email_notification_add_tracking_section: this.emailNotificationAddTrackingSection}, {
          complete: () => {
            this.emailNotificationsDisabled.email_notification_add_tracking_section = false
          },
          error: () => {
            // 失败就还原选项
            this.emailNotificationAddTrackingSection = !this.emailNotificationAddTrackingSection
          },
        })
      },

      // 保存 Track 按钮 开关
      saveOrdersPageAddTrackButton() {
        this.emailNotificationsDisabled.orders_page_add_track_button = true
        this.settingsSave({orders_page_add_track_button: this.ordersPageAddTrackButton}, {
          complete: () => {
            this.emailNotificationsDisabled.orders_page_add_track_button = false
          },
          error: () => {
            // 失败就还原选项
            this.ordersPageAddTrackButton = !this.ordersPageAddTrackButton
          },
        })
      },

      // 保存 status shipped label 开关
      saveIsStatusShipped() {
        this.emailNotificationsDisabled.status_shipped = true
        this.settingsSave({status_shipped: this.isStatusShipped}, {
          complete: () => {
            this.emailNotificationsDisabled.status_shipped = false
          },
          error: () => {
            // 失败就还原选项
            this.isStatusShipped = !this.isStatusShipped
          },
        })
      },

      // 保存 Add Tracking 按钮 开关
      saveAdminOrderActionsAddTrack() {
        this.emailNotificationsDisabled.admin_order_actions_add_track = true
        this.settingsSave({admin_order_actions_add_track: this.adminOrderActionsAddTrack}, {
          complete: () => {
            this.emailNotificationsDisabled.admin_order_actions_add_track = false
          },
          error: () => {
            // 失败就还原选项
            this.adminOrderActionsAddTrack = !this.adminOrderActionsAddTrack
          },
        })
      },

      // Ajax 保存设置
      settingsSave(data, {complete, success, error} = {}) {
        $.ajax({
          type: 'POST',
          url: `${ ajaxurl }?action=pp_settings_save&_ajax_nonce=${ pp_settings_save_nonce }`,
          contentType: 'application/json',
          data: JSON.stringify(data),
          complete: () => {
            complete?.()
          },
          success: res => {
            if (res.success) {
              $.toastr.success(res.msg)
            } else {
              $.toastr.error(res.msg)
            }
            success?.()
          },
          error() {
            $.toastr.error('Server error, please try again or refresh the page')
            error?.()
          },
        })
      },

      // Ajax 保存运输商设置
      courierMatchingSave() {
        this.courierListLoading = true

        const data = {
          enable: this.tmpEnabledExpressList,
          disable: this.tmpDisabledExpressList,
        }
        this.tmpEnabledExpressList = []
        this.tmpDisabledExpressList = []
        $.ajax({
          type: 'POST',
          url: ajaxurl,
          data: {
            ...data,
            action: 'pp_courier_matching_save',
            _ajax_nonce: pp_courier_matching_save_nonce,
          },
          complete: () => {
            this.courierListLoading = false
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
    },
  })
})
