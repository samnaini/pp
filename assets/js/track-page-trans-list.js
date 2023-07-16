(() => {
  const ChineseSimpleWords = {
    'order_number': '订单号',
    'expected_delivery': '预计投递时间',
    'or': '或者',
    'email': '邮箱',
    'track': '查询',
    'order': '订单',
    'tracking_number': '快递单号',
    'product': '商品',
    'carrier': '运输商',
    'status': '状态',
    'order_not_found': '无法找到您的订单',
    'enter_your_order': '请输入订单号',
    'enter_your_email': '请输入邮箱',
    'enter_your_tracking_number': '请输入快递单号',
    'not_yet_shipped': '商品尚未发货',
    'ordered': '下单',
    'order_ready': '准备就绪',
    'waiting_updated': '等待运输商更新信息，请稍后',
    'shipping_to': '目的地',
    'current_location': '当前位置',
    'may_like': '你可能也喜欢',
    'pending': '待处理',
    'in_transit': '运输途中',
    'out_for_delivery': '到达待取',
    'delivered': '投递完成',
    'expired': '运输超时',
    'failed_attempt': '尝试投递失败',
    'exception': '运输异常',
    'info_received': '待揽件',
  }

  const DutchWords = {
    'order_number': 'Bestelnummer',
    'expected_delivery': 'Verwachte leverdatum',
    'or': 'Of',
    'email': 'E-Mail',
    'track': 'Volgen',
    'order': 'Bestelling',
    'tracking_number': 'Traceernummer',
    'product': 'Product',
    'carrier': 'Vervoerder',
    'status': 'Status',
    'order_not_found': 'Kon de bestelling niet vinden',
    'enter_your_order': 'Voer uw ordernummer in',
    'enter_your_email': 'Voer uw e-mail adres in',
    'enter_your_tracking_number': 'Voer uw volgnummer in',
    'not_yet_shipped': 'Deze artikelen zijn nog niet verzonden',
    'ordered': 'Besteld',
    'order_ready': 'Bestelling klaar',
    'waiting_updated': 'De bezorgdienst heeft de tracking informatie nog niet geupdate, probeer het later nog eenkeer',
    'shipping_to': 'Sturen naar',
    'current_location': 'Huidige locatie',
    'may_like': 'Misschien vind je dit ook leuk... ',
    'pending': 'In afwachting van',
    'in_transit': 'Onderweg',
    'out_for_delivery': 'Levering',
    'delivered': 'Geleverd',
    'expired': 'Verlopen',
    'failed_attempt': 'Mislukte levering',
    'exception': 'Uitzondering',
    'info_received': 'Informatie krijgen',
  }

  const FrenchWords = {
    'order_number': 'Numéro de commande',
    'expected_delivery': 'Date de livraison prévue',
    'or': 'Ou',
    'email': 'Email',
    'track': 'Suivre',
    'order': 'Commande',
    'tracking_number': 'Numéro de suivi',
    'product': 'Produit',
    'carrier': 'Transporteur',
    'status': 'Statut',
    'order_not_found': 'Impossible de trouver la commande',
    'enter_your_order': 'Entrez votre numéro de commande',
    'enter_your_email': 'Entrez votre email',
    'enter_your_tracking_number': 'Entrez votre numéro de suivi',
    'not_yet_shipped': 'Ces articles n’ont pas encore été expédiés',
    'ordered': 'Commandé',
    'order_ready': 'Commande prête',
    'waiting_updated': 'En attendant que le transporteur mette à mis à jour les informations de suivi, veuillez réessayer plus tard',
    'shipping_to': 'Livraison à',
    'current_location': 'Localisation actuel',
    'may_like': 'Vous pouvez aussi aimer...',
    'pending': 'En attente',
    'in_transit': 'En transit',
    'out_for_delivery': 'En cours de livraison',
    'delivered': 'Livré',
    'expired': 'Expiré',
    'failed_attempt': 'Tentative de livraison ratée',
    'exception': 'Exception',
    'info_received': 'Info reçu',
  }

  const GermanWords = {
    'order_number': 'Bestellnummer',
    'expected_delivery': 'Erwartetes Lieferdatum',
    'or': 'Oder',
    'email': 'Email',
    'track': 'Verfolgen',
    'order': 'Bestellung',
    'tracking_number': 'Versandnummer',
    'product': 'Produkt',
    'carrier': 'Spediteur',
    'status': 'Status',
    'order_not_found': 'Bestellung konnte nicht gefunden werden',
    'enter_your_order': 'Gebe deine Bestellnummer ein',
    'enter_your_email': 'Geben Sie ihre E-Mail ein',
    'enter_your_tracking_number': 'Bitte gib deine Versandnummer ein',
    'not_yet_shipped': 'Die Produkte wurden noch nicht versendet',
    'ordered': 'Bestellt',
    'order_ready': 'Bestellung bereit',
    'waiting_updated': 'Warte auf Übermittlung der Spediteurs, versuche es spätererneut',
    'shipping_to': 'Versenden an',
    'current_location': 'Aktuelle Position',
    'may_like': 'Das könnte dir auch gefallen…',
    'pending': 'Ausstehend',
    'in_transit': 'Unterwegs',
    'out_for_delivery': 'Zustellung läuft',
    'delivered': 'Zugestellt',
    'expired': 'Abgelaufen',
    'failed_attempt': 'Fehlgeschlagener Zustellversuch',
    'exception': 'Ausnahme',
    'info_received': 'Informationen erhalten',
  }

  const ItalianWords = {
    'order_number': 'Numero ordine',
    'expected_delivery': 'Data di consegna prevista',
    'or': 'O',
    'email': 'Email',
    'track': 'Traccia',
    'order': 'Ordine',
    'tracking_number': 'Numero di tracciabilità',
    'product': 'Prodotto',
    'carrier': 'Corriere',
    'status': 'Stato',
    'order_not_found': 'Impossibile trovare l\'ordine',
    'enter_your_order': 'Per favore inserisci il numero del tuo ordine',
    'enter_your_email': 'Per favore inserisci la tua email',
    'enter_your_tracking_number': 'Per favore inserisci il tuo numero di tracciabilità',
    'not_yet_shipped': 'Questi articoli non sono ancora stati spediti',
    'ordered': 'Ordinato',
    'order_ready': 'Ordine pronto',
    'waiting_updated': 'In attesa che il corriere aggiorni le informazioni di tracciamento, riprova più tardi',
    'shipping_to': 'Spedire a',
    'current_location': 'Posizione attuale',
    'may_like': 'Potrebbe piacerti anche...',
    'pending': 'In attesa',
    'in_transit': 'In transito',
    'out_for_delivery': 'In consegna',
    'delivered': 'Consegnato',
    'expired': 'Scaduto',
    'failed_attempt': 'Tentativo fallito',
    'exception': 'Eccezione',
    'info_received': 'Informazioni ricevute',
  }

  const SpanishWords = {
    'order_number': 'Número de pedido',
    'expected_delivery': 'Fecha de entrega estimada',
    'or': 'O',
    'email': 'Email',
    'track': 'Localizar',
    'order': 'Pedido',
    'tracking_number': 'Número de seguimiento',
    'product': 'Producto',
    'carrier': 'Transportista',
    'status': 'Estado',
    'order_not_found': 'Pedido no encontrado',
    'enter_your_order': 'Introduce tu número de pedido',
    'enter_your_email': 'Introduce tu email',
    'enter_your_tracking_number': 'Introduce tu número de pedido',
    'not_yet_shipped': 'Estos artículos todavía no se han enviado',
    'ordered': 'Pedido',
    'order_ready': 'Pedido listo',
    'waiting_updated': 'Pendiente de actualización por la compañía de transporte. Por favor, inténtalo de nuevo más tarde',
    'shipping_to': 'Enviado a',
    'current_location': 'Localización actual',
    'may_like': 'También te puede gustar...',
    'pending': 'Pendiente',
    'in_transit': 'En tránsito',
    'out_for_delivery': 'En entrega',
    'delivered': 'Entregado',
    'expired': 'Expirado',
    'failed_attempt': 'Intento de entrega fallido',
    'exception': 'Excepción',
    'info_received': 'Información recibida',
  }

  window.PP_TRACK_PAGE_TRANS_LIST = {
    'zh-Hans': ChineseSimpleWords,
    'nl': DutchWords,
    'fr': FrenchWords,
    'de': GermanWords,
    'it': ItalianWords,
    'es': SpanishWords,
  }
})()