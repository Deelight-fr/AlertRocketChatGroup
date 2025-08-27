<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;


//if (!defined('DOKU_INC')) die();

//require_once (DOKU_INC.'inc/changelog.php');

//if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
//if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
//if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');



/**
 * DokuWiki Plugin alertrocketchatgroup (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author JPDroid <jpdroid.jpo@gmail.com>
 */
class action_plugin_alertrocketchatgroup extends ActionPlugin
{
    /** @inheritDoc */
    public function register(EventHandler  $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleActionActPreprocess');

    }


    /**
     * Event handler for ACTION_ACT_PREPROCESS
     *
     * @see https://www.dokuwiki.org/devel:events:ACTION_ACT_PREPROCESS
     * @param Event $event Event object
     * @param mixed $param optional parameter passed when event was registered
     * @return void
     */
    function handleActionActPreprocess(Event $event)
    {
		if ($event->data == 'save') {
			$this->handle();
		}
		return;
    }


	private function handle() {
		global $conf;
		global $ID;
		global $INFO;
		global $SUM;

		// filter by namespaces
		$urlServer = $this->getConf('jurlserver');
		$payloads = $this->getConf('jpayloads');
		if (!empty($urlServer)) {
		  $arrPayloads = explode(',', $payloads);
		}

        $fullname = $INFO['userinfo']['name'];
        $uid = $INFO['userinfo']['uid'];
    	$page     = $INFO['namespace'] . $INFO['id'];
        $title    = "{$uid} a mis à jour le wiki : {$this->urlize()}";

        foreach($arrPayloads as $idPayload => $pgPayload)
        {

				if(!isset($pgPayload) || $pgPayload == '')
				  return;

				// text
				$data = array(
                    "text"                  =>  $title,
				);
				
				// init curl
                $json = json_encode($data);

				$webhook = $urlServer;
				$urlFullWebHook =  $webhook.'/hooks/'.$pgPayload;
				$ch = curl_init($urlFullWebHook);
				 // submit payload
				 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				 curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
				 $result = curl_exec($ch);

				 // ideally display only for Admin users and/or in debugging mode
				 if ($result === false){
				   echo 'cURL error when posting Wiki save notification to Rocket.Chat+: ' . curl_error($ch);
				 }
			 
				 // close curl
				 curl_close($ch);
			
        }

	}


	private function urlize($diffRev=null) {

		global $conf;
		global $INFO;
	
		switch($conf['userewrite']) {
		case 0:
		  if (!empty($diffRev)) {
			$url = DOKU_URL . "doku.php?id={$INFO['id']}&rev={$diffRev}&do=diff";
		  } else {
			$url = DOKU_URL . "doku.php?id={$INFO['id']}";
		  }
		  break;
		case 1:
		  $id = $INFO['id'];
		  if ($conf['useslash']) {
			$id = str_replace(":", "/", $id);
		  }
		  if (!empty($diffRev)) {
			$url = DOKU_URL . "{$id}?rev={$diffRev}&do=diff";
		  } else {
			$url = DOKU_URL . $id;
		  }
		  break;
		case 2:
		  $id = $INFO['id'];
		  if ($conf['useslash']) {
			$id = str_replace(":", "/", $id);
		  }
		  if (!empty($diffRev)) {
			$url = DOKU_URL . "doku.php/{$id}?rev={$diffRev}&do=diff";
		  } else {
			$url = DOKU_URL . "doku.php/{$id}";
		  }
		  break;
		}
		return $url;
	  }
	

}
