jQuery(($) => {
  new PPVue({
    el: '#pp-app',
    data: function () {
      return {
        activatedTabIndex: 0,
        pluginIntegrationEnable: pp_integration_enabled_config,
        toggleDisabled: {},
      }
    },
    methods: {
      activeTab(index) {
        this.activatedTabIndex = index
      },

      savePluginIntegrationEnabled(id, isEnabled) {
        this.$set(this.toggleDisabled, id, true)
        this.settingsSave({
          app_id: id,
          enabled: isEnabled,
        }, {
          complete: () => {
            this.$set(this.toggleDisabled, id, false)
          },
          error: () => {
            // 失败就还原选项
            this.pluginIntegrationEnable[id] = !this.pluginIntegrationEnable[id]
          },
        })
      },

      onContactUs() {
        window.PPLiveChat('showNewMessage', 'Hi support, I would like to suggest you integrate with a plugin that is not on your list.')
      },

      settingsSave(data, {complete, success, error} = {}) {
        $.ajax({
          type: 'POST',
          url: `${ ajaxurl }?action=pp_integration_switch&_ajax_nonce=${ pp_integration_switch_nonce }`,
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
    },
  })
})
