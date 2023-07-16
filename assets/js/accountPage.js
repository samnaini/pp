jQuery(($) => {
  const statusRequest = {
    LOADING: 1,
    FINISHED: 2,
  }
  const cycleUnit = {
    1: ['dy.', 'day'],
    7: ['wk.', 'week'],
    30: ['mo.', 'month'],
  }
  new PPVue({
    el: '#pp-app',
    data: {
      statusRequest,
      reqStatus: statusRequest.LOADING,
      i18n: pp_plans.i18n,
      chosenPlanId: null,
      isOpenModal: false,
      isOpenRatingModal: false,
      isOpenFeedbackModal: false,
      planId: null,
      quota: 0,
      quotaUsed: 0,
      summary: '',
      planName: 'Unknown',
      expiredDate: '',
      isUnlimitedPlan: false,  // 是否正在使用不限额度套餐
      plans: [],
      urlLoginDashboard: null,  // 用户后台登录地址
      // 星级评价
      selectedRating: 0,
      // 反馈
      fbMsg: '',
      fbEmail: pp_plans.user_email,
      isSendingFeedback: false,
    },
    created() {
      this.getPlans()
    },
    methods: {
      getPlans() {
        $.get(ajaxurl, {
          action: 'pp_get_plan',
          _ajax_nonce: pp_plans.read_nonce,
        }, e => {
          if (e.success) {
            const respData = e.data

            if (respData) {
              this.planId = respData.current_plan.pid || null

              const regEmoji = new RegExp('((\ud83c[\udc00-\udfff])|(\ud83d[\udc00-\udfff])|(\ud83e[\udd00-\uddff])|[\u2600-\u2B55])', 'g')

              this.plans = respData.data.map(v => {
                if (!v.title) {
                  if (v.price && cycleUnit[v.cycle]) {
                    v.name += ` $${ Math.round(v.price / 100) }/${ cycleUnit[v.cycle][0] }`
                  }
                  v.title = v.name  // 显示标题与套餐名称
                }

                // 处理 emoji
                v.title = v.title.replace(regEmoji, '<i class="pp-emj">$1</i>')

                v.loading = false
                return v
              })

              if (this.planId) {
                this.quota = respData.current_plan.quota || 0
                this.quotaUsed = respData.current_plan.quota_used || 0
                this.expiredDate = respData.current_plan.expired_date || ''
                this.isUnlimitedPlan = !!respData.current_plan.is_unlimited_plan

                if (this.currentPlan) {
                  this.planName = this.currentPlan.name || 'Unknown'

                  const cycle = this.currentPlan.cycle || 0

                  this.summary = cycleUnit[cycle] ? `${ this.quota } quota per ${ cycleUnit[cycle][1] }` : 'Unknown'
                }
              }

              this.urlLoginDashboard = respData.url_login_dashboard || null
            } else {
              $.toastr.error('The ParcelPanel server is busy. Please try again later!')
            }

            this.reqStatus = statusRequest.FINISHED
          } else {
            $.toastr.error(e.msg)
          }
        }, 'json')
      },

      // 生成套餐链接
      getPlanLink(planId) {
        $.ajax({
          url: ajaxurl,
          data: {
            action: 'pp_get_plan_link',
            _ajax_nonce: pp_plans.link_nonce,
            pid: planId,
          },
          dataType: 'json',
          success: (e) => {
            if (e.success) {
              this.openNewTab(e.data.link)
            } else {
              $.toastr.error(e.msg)
            }
          },
          error() {
            $.toastr.error('Server error, please try again or refresh the page')
          },
        })
      },

      // 选择套餐
      choosePlan(planId, freeOk = false) {
        const plan = this.plans.find(v => planId === v.id)
        if (!plan) {
          return
        }
        if (!freeOk && 1 === planId) {
          this.isOpenModal = true
          return
        }

        if (plan.is_unlimited_quota) {
          this.onChooseUnlimitedPlan()
          return
        }

        this.chosenPlanId = planId

        this.getPlanLink(planId)
      },

      // 套餐降级
      freeOk() {
        // this.choosePlan(1, true)
        $.post(ajaxurl, {
          action: 'pp_drop_free_ajax',
          _ajax_nonce: pp_plans.read_nonce,
        }, e => {
          if (e.success) {
            $.toastr.success(e.msg || 'Updated successfully')
            this.isOpenModal = false
            setTimeout(() => {
              location.reload()
            }, 300)
          } else {
            $.toastr.error(e.msg || 'Updated failed')
          }
        }, 'json')
      },

      onChooseUnlimitedPlan() {
        window.PPLiveChat('showNewMessage', 'Hi support, I would like to free upgrade to the Unlimited plan.')
      },

      // 打开新页面
      openNewTab(url) {
        window.open(url)
      },

      onNeedAnyHelpClick() {
        window.PPLiveChat('showNewMessage', '')
      },

      onRatingClick(v) {
        this.selectedRating = v
        if (5 === v) {
          const url = 'https://wordpress.org/support/plugin/parcelpanel/reviews/#new-post'
          const a = $('<a href="' + url + '" target="_blank"></a>')
          const d = a.get(0)
          if (document.createEvent) {
            const e = document.createEvent('MouseEvents')
            e.initEvent('click', true, true)
            d.dispatchEvent(e)
            a.remove()
          } else if (document.all) {
            d.click()
          }
        } else {
          this.isOpenFeedbackModal = true
        }
      },

      onSendFeedback() {
        let data = {
          action: 'pp_feedback_confirm',
          _ajax_nonce: pp_plans.feedback_confirm_nonce,
          msg: this.fbMsg,
          email: this.fbEmail,
          rating: this.selectedRating,
          type: 2,
        }

        this.isSendingFeedback = true

        $.ajax({
          url: ajaxurl,
          data: data,
          type: 'POST',
          complete() {
            this.isSendingFeedback = false
          },
          success: function (resp) {
            if (resp.success) {
              this.isOpenRatingModal = false
              this.isOpenFeedbackModal = false
              let msg = 'Sent successfully'
              $.toastr.success(msg)
              setTimeout(() => {
                location.reload()
              }, 300)
            } else {
              $.toastr.error(resp.msg)
            }
          },
          error() {
            let msg = 'Server error, please try again or refresh the page'
            $.toastr.error(msg)
          },
        })
      },
    },
    computed: {
      currentPlan() {
        return this.plans.find(v => v.id === this.planId)
      },
      quotaRemain() {
        return this.quota - this.quotaUsed
      },
      quotaProgressWidth() {
        return this.quota ? 100 - this.quotaUsed / this.quota * 100 : 0
      },
    },
  })
})
