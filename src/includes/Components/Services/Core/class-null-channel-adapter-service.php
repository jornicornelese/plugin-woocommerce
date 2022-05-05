<?php

namespace Biller\Components\Services;

use Biller\BusinessLogic\Notifications\Interfaces\ShopNotificationChannelAdapter;
use Biller\BusinessLogic\Notifications\Model\Notification;


class Null_Channel_Adapter_Service implements ShopNotificationChannelAdapter {

	public function push( Notification $notification ) {
	}
}