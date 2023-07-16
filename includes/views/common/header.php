<?php
/**
 * @author Chuwen
 * @date   2021/7/26 16:46
 */

defined( 'ABSPATH' ) || exit;
?>
<header id="pp-header-root" class="pp-layout__header">
  <div class="logo">
    <img src="<?php echo esc_url( parcelpanel_get_assets_path( 'imgs/logo.png' ) ); ?>" alt="<?php esc_attr_e( 'ParcelPanel logo', 'parcelpanel' ) ?>"/>
  </div>
  <div class="tool-operate">
    <button id="PP-Header-Btn-GetHelp" class="pp-header__tool-operate-item">
      <svg class="icon" viewBox="0 0 1025 1024" xmlns="http://www.w3.org/2000/svg">
        <path d="M512.268 1022.836c-68.658 0-135.4-13.565-198.37-40.317-60.752-25.809-115.373-62.713-162.346-109.685-46.971-46.972-83.875-101.593-109.685-162.347C15.116 647.517 1.55 580.777 1.55 512.12s13.565-135.4 40.316-198.37c25.81-60.752 62.714-115.373 109.685-162.346C198.525 104.43 253.146 67.527 313.9 41.717c62.97-26.75 129.71-40.315 198.37-40.315s135.398 13.564 198.368 40.315c60.752 25.81 115.373 62.714 162.346 109.686 46.972 46.973 83.876 101.594 109.686 162.346 26.752 62.97 40.316 129.711 40.316 198.37s-13.564 135.398-40.316 198.368c-25.81 60.754-62.713 115.375-109.686 162.347-46.972 46.972-101.593 83.876-162.346 109.685-62.97 26.752-129.711 40.317-198.369 40.317zm0-972.288c-62.019 0-122.294 12.248-179.152 36.403-54.923 23.334-104.318 56.71-146.81 99.205s-75.872 91.888-99.205 146.81C62.945 389.825 50.698 450.1 50.698 512.12c0 62.018 12.247 122.293 36.403 179.152 23.333 54.923 56.71 104.318 99.204 146.812 42.493 42.493 91.889 75.87 146.811 99.204 56.858 24.156 117.133 36.403 179.152 36.403 62.018 0 122.293-12.247 179.153-36.403 54.923-23.333 104.317-56.71 146.811-99.204 42.494-42.494 75.871-91.889 99.205-146.812 24.155-56.858 36.403-117.133 36.403-179.152s-12.248-122.294-36.403-179.153c-23.335-54.923-56.711-104.317-99.206-146.81-42.493-42.494-91.887-75.871-146.81-99.205-56.86-24.155-117.135-36.403-179.153-36.403z"/>
        <path d="M509.636 662.04c-12.807 0-23.602-9.926-24.49-22.895-3.938-57.461 9.411-96.389 21.305-118.924 10.555-20.005 31.44-38.654 41.477-47.616 1.052-.939 1.926-1.717 2.57-2.31 52.085-48.132 51.437-78.39 51.308-80.622a22.475 22.475 0 0 1-.136-.906c-3.954-30.151-17.538-50.213-41.528-61.33-13.283-6.156-29.04-9.305-46.834-9.357a199.72 199.72 0 0 1-1.389-.008 313.93 313.93 0 0 0-3.434-.026c-37.412.05-62.803 15.917-77.62 48.504-11.748 25.835-12.075 52.357-12.075 52.618 0 13.572-11.002 24.574-24.574 24.574s-24.573-11.002-24.573-24.574c0-1.499.186-37.122 16.484-72.962 10.053-22.11 24.098-39.887 41.744-52.838 22.063-16.194 49.165-24.426 80.554-24.468 1.393-.008 2.764.011 4.143.03l.885.004c24.936.072 47.596 4.754 67.357 13.912 27.368 12.684 61.59 40.135 69.509 98.92.913 5.398 2.022 19.36-5.167 39.884-9.654 27.563-30.277 56.073-61.3 84.74-.798.737-1.882 1.707-3.188 2.875-6.652 5.94-24.32 21.716-30.745 33.893-8.918 16.901-18.888 46.689-15.74 92.63.927 13.54-9.296 25.268-22.836 26.195a26.59 26.59 0 0 1-1.707.056zm92.379-271.282l.006.03-.006-.03zm-.006-.027l.005.022-.005-.022zm-.213-1.19zM513.811 706.462c16.42 0 29.73 13.297 29.73 29.715v7.87a29.649 29.649 0 0 1-29.73 29.714c-16.419 0-29.73-13.296-29.73-29.714v-7.87a29.649 29.649 0 0 1 29.73-29.715z"/>
      </svg>
      <span class="text"><?php esc_html_e( 'Get help', 'parcelpanel' ) ?></span>
    </button>
  </div>
</header>
<div id="PP-Header-GetHelpPopoverMenu" class="PP-Popover" style="display:none">
  <ul class="menus">
    <li>
      <button id="PP-Popover-Btn-LiveChat" class="item">
        <svg class="icon" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
          <path d="M928 736c-4 0-8.032-.736-11.904-2.272-58.528-23.424-115.072-61.056-168.032-111.808a32 32 0 1 1 44.32-46.176c26.048 24.96 52.928 46.304 80.384 63.808a1113.728 1113.728 0 0 1-28.416-121.92 32.032 32.032 0 0 1 10.016-29.152C901.856 445.44 928 391.328 928 336c0-132.352-143.552-240-320-240S288 203.648 288 336c0 3.904.128 7.744.384 11.584a32 32 0 0 1-29.856 34.016c-17.824.512-32.864-12.224-34.016-29.856A233.088 233.088 0 0 1 224 336c0-167.616 172.256-304 384-304s384 136.384 384 304c0 68.16-28.832 134.08-81.568 187.392 18.048 94.624 47.008 168 47.296 168.736a31.936 31.936 0 0 1-7.136 34.496A31.936 31.936 0 0 1 928 736z"/>
          <path d="M96 992a32 32 0 0 1-29.76-43.84c.32-.736 29.248-74.112 47.296-168.736C60.832 726.048 32 660.16 32 592c0-167.616 172.256-304 384-304s384 136.384 384 304-172.256 304-384 304c-47.296 0-93.504-6.848-137.76-20.352-53.664 51.936-110.88 90.272-170.368 114.048A31.36 31.36 0 0 1 96 992zm320-640C239.552 352 96 459.648 96 592c0 55.296 26.144 109.44 73.632 152.48 8.096 7.36 11.904 18.336 10.016 29.152a1108.832 1108.832 0 0 1-28.416 121.952C184 874.688 215.904 848.32 246.688 816.8a32 32 0 0 1 33.6-7.808C323.424 824.256 369.088 832 416 832c176.448 0 320-107.648 320-240S592.448 352 416 352z"/>
          <path d="M192 624a32 32 0 0 1-32-32c0-84.992 102.88-176 256-176a32 32 0 1 1 0 64c-117.216 0-192 66.336-192 112a32 32 0 0 1-32 32z"/>
        </svg>
        <span class="text"><?php esc_html_e( 'Live chat Support', 'parcelpanel' ) ?></span>
      </button>
    </li>
    <li>
      <a href="https://docs.parcelpanel.com/woocommerce/" target="_blank" id="PP-Popover-Btn-HelpDocs" class="item">
        <svg class="icon" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
          <path d="M896 64H234.656C157.728 64 96 128.8 96 208c0 5.472.992 10.688 1.536 16H96v576c0 88.032 68.544 160 153.92 160H896V288H234.656C193.792 288 160 252.512 160 208s33.792-80 74.656-80H896V64zM464 352h176v172.192l-68.192-53.696a31.968 31.968 0 0 0-39.584 0L464 524.192V352zm-229.344 0H400v238.112a32 32 0 0 0 51.808 25.152L552 536.352l100.192 78.912A32 32 0 0 0 704 590.112V352h128v544H249.92c-49.312 0-89.92-42.656-89.92-96V329.024C181.536 343.392 207.04 352 234.656 352z"/>
          <path d="M255.776 176H832v64H255.776z"/>
        </svg>
        <span class="text"><?php esc_html_e( 'ParcelPanel Help Docs', 'parcelpanel' ) ?></span>
      </a>
    </li>
    <li>
      <a href="mailto:support@parcelpanel.org" target="_blank" id="PP-Popover-Btn-EmailSupport" class="item">
        <svg class="icon" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
          <path d="M974.507 201.387A85.333 85.333 0 0 0 896 149.333H128a85.333 85.333 0 0 0-85.333 85.334v554.666A85.333 85.333 0 0 0 128 874.667h768a85.333 85.333 0 0 0 85.333-85.334V234.667a85.333 85.333 0 0 0-6.826-33.28zm-102.4 11.946l-352.64 352.64a10.667 10.667 0 0 1-15.147 0l-352.427-352.64zm45.226 576A21.333 21.333 0 0 1 896 810.667H128a21.333 21.333 0 0 1-21.333-21.334V258.56l352.64 352.64a74.667 74.667 0 0 0 105.6 0l352.426-352.64z"/>
        </svg>
        <span class="text"><?php esc_html_e( 'Get Email Support', 'parcelpanel' ) ?></span>
      </a>
    </li>
  </ul>
</div>
<script>
  jQuery(($) => {
    const $BtnGetHelp = $('#PP-Header-Btn-GetHelp')
    const $GetHelperPopoverMenu = $('#PP-Header-GetHelpPopoverMenu')

    $BtnGetHelp.on('click', () => {
      const top = $BtnGetHelp.innerHeight() + $BtnGetHelp.offset().top - $(window).scrollTop()
      const right = $(window).width() - $BtnGetHelp.innerWidth() - $BtnGetHelp.offset().left
      $GetHelperPopoverMenu.css({top, right})
      $GetHelperPopoverMenu.show()
      $(window).on('click', handleWindowClick)
    })

    $GetHelperPopoverMenu
      .on('click', '#PP-Popover-Btn-LiveChat', handlePopoverButtonClick(handleLiveChatClick))

    function handlePopoverButtonClick(callback) {
      return (e) => {
        $(window).off('click', handleWindowClick)
        $GetHelperPopoverMenu.hide()
        callback?.(e)
      }
    }

    function handleLiveChatClick(e) {
      window.PPLiveChat('show')
    }

    function handleWindowClick(e) {
      if (!$BtnGetHelp.is(e.target) && !$BtnGetHelp.has(e.target).length) {
        $(window).off('click', handleWindowClick)
        $GetHelperPopoverMenu.hide()
      }
    }
  })
</script>