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
		$grupos = $this->getConf('jgroups');
		if (!empty($urlServer)) {
		  $arrPayloads = explode(',', $payloads);
		}
		if (!empty($grupos)) {
			$arrGrupos = explode(',', $grupos);
		}

		$fullname = $INFO['userinfo']['name'];
    	$page     = $INFO['namespace'] . $INFO['id'];
   		$title    = "{$fullname} atualizou a TRIDF-WIKI <{$this->urlize()}|{$INFO['id']}>";

		//enviar mensagem para os grupos
		foreach($arrGrupos as $chave =>$gpr){
			if(explode(':',$INFO['id'])[0] == $gpr){
				$pgIndex = $chave;
				$pgNome = $gpr;
				$pgPayload = $arrPayloads[$pgIndex];
				if(!isset($pgPayload) || $pgPayload == '')
				  return;


				// text
				$data = array(
					"text"                  =>  $title
				);
				
				// attachments
				// if (!empty($SUM)) {
					// $data['attachments'] = array(array(
					// "title_link"       => "{$this->urlize()}",
					// "title"            => "Summary",
					// "text"             => "{$SUM}\n- {$fullname}",
					// "color"            => "#AB4531"
					// ));
				// }

				// init curl
				$json = json_encode($data);
				$webhook = $urlServer;
				$urlFullWebHook =  $webhook.'/hooks/'.$pgPayload;
				$ch = curl_init($urlFullWebHook);
				 // submit payload
				 $pay = urlencode($json);
				 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				 curl_setopt($ch, CURLOPT_POSTFIELDS, "payload={$pay}");
				 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				 $result = curl_exec($ch);
			 
				 // ideally display only for Admin users and/or in debugging mode
				 if ($result === false){
				   echo 'cURL error when posting Wiki save notification to Rocket.Chat+: ' . curl_error($ch);
				 }
			 
				 // close curl
				 curl_close($ch);
			 

			
			}
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
