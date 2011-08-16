<?php

/*
 * Description: Decodes/encodes short URL's, tracks uniques, and redirects
 * Author: Jeffrey Clark
 */

class Bounce
{
    protected 
        $item = array(),
        $clicker = array(),
        $clicker_headers_sent = FALSE,
        $clicker_type = NULL;

    private static
        $dbHandle;

    private
        $item_id,
        $clicker_id,
        $send_tracking = FALSE;

    const
        T_PREFIX = 'bounce_',
        CLICKER_TYPE_UNKNOWN = NULL,
        CLICKER_TYPE_NEW = 1,
        CLICKER_TYPE_COOKIE = 2,
        CLICKER_TYPE_ETAG = 3;

    public static function To($bounceTo) {
        try {
            $obj = new Bounce($bounceTo);
            $obj->send_tracking = TRUE;
        } catch(Exception $e) {
            return FALSE;
        }
        return $obj;
    }

    public function __construct($bounceTo) {
        $bounceTo = (string) $bounceTo;

        $this->item_id = self::decode($bounceTo, 62, 10);
    }

    static function setPDO($pdo) {
        self::$dbHandle = $pdo;
    }

    public function findClicker() {
        foreach ($_COOKIE as $key => $value) {
            if (is_numeric($value) && self::decodeClicker($key, $this->clicker_id)) {
                if($this->lookupClicker($key)) {
                    $this->clicker_type = Bounce::CLICKER_TYPE_COOKIE;
                    return TRUE;
                }
            }
        }
        if (!$this->clicker_id) {
            if (array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER)) {
                if (self::decodeClicker($_SERVER['HTTP_IF_NONE_MATCH'], $this->clicker_id)) {
                    if ($this->lookupClicker($_SERVER['HTTP_IF_NONE_MATCH'])) {
                        $this->clicker_type = Bounce::CLICKER_TYPE_ETAG;
                        return TRUE;
                    }
                }
            }
        }
        return FALSE;
    }

    public function createClicker() {
        $stmt = self::$dbHandle->prepare('INSERT INTO '.self::T_PREFIX.'clickers (user_agent) VALUES (?)');
        if ($stmt->execute(array($_SERVER['HTTP_USER_AGENT']))) {
            $this->clicker_type = Bounce::CLICKER_TYPE_NEW;
            $this->clicker['id'] = self::$dbHandle->lastInsertId();
            if (self::encodeClicker($this->clicker['id'], $this->clicker['tracker'])) {
                $this->sendClickerTracker();
                return TRUE;
            }
        }
        return FALSE;
    }

    public function lookupClicker($existingTracker = NULL) {
        $stmt = self::$dbHandle->query('SELECT * FROM '.self::T_PREFIX.'clickers WHERE id = ' . $this->clicker_id, PDO::FETCH_ASSOC);
        if ($row = $stmt->fetch()) {
            $this->clicker = $row;
            if ($existingTracker) {
                $this->clicker['tracker'] = $existingTracker;
            } else {
                self::encodeClicker($this->clicker['id'], $this->clicker['tracker']);
            }
            $this->sendClickerTracker();
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function findItem() {
        $stmt = self::$dbHandle->query('SELECT * FROM '.self::T_PREFIX.'items WHERE id = ' . $this->item_id, PDO::FETCH_ASSOC);
        if ($stmt && $row = $stmt->fetch()) {
            $this->item = $row;
            $this->sendItemTracker();
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function sendItemTracker() {
        static $sent = FALSE;

        if (empty($this->item['created_at']) || !$this->send_tracking) {
            return FALSE;
        }

        if (!$sent) {
            header('Cache-Control: public, must-revalidate');
            header('Last-Modified: ' . date('r', $this->item['created_at']));
            $sent = TRUE;
        }

        return TRUE;
    }

    public function sendClickerTracker() {
        static $sent = FALSE;

        if (empty($this->clicker['tracker']) || !$this->send_tracking) {
            return FALSE;
        }

        if (!$sent) {
            header('ETag: ' . $this->clicker['tracker']);
            setcookie($this->clicker['tracker'], time(), time()+60*60*24*365, '/');
        }

        return TRUE;
    }

    public function go() {
        $stmt = self::$dbHandle->prepare(
            'INSERT INTO '.self::T_PREFIX.'clicks '
            . '(bounce_item_id, bounce_clicker_id, ip_address, user_agent, referer) '
            . 'VALUES (?, ?, ?, ?, ?)'
        );
        if ($stmt) {
            $referer = (array_key_exists('HTTP_REFERER', $_SERVER)) ? $_SERVER['HTTP_REFERER'] : NULL;
            $user_agent = (array_key_exists('HTTP_USER_AGENT', $_SERVER)) ? $_SERVER['HTTP_USER_AGENT'] : NULL;
            $stmt->execute(array(
                $this->item['id'],
                $this->clicker['id'],
                ip2long($_SERVER['REMOTE_ADDR']),
                $user_agent,
                $referer
            ));
        }

        if ($this->item['disabled']) {
            return FALSE;
        }

        header('Location: ' . $this->item['url'], TRUE, 302);
    }

    static function encodeClicker($id, &$encoded) {
        if (!is_numeric($id)) {
            return FALSE;
        }

        $base = array_reverse(str_split(time()));
        $id = str_split((string)$id);
        $mixed = "";

        for($i=0; $i<count($id); $i++) {
            $mixed .= $id[$i] . $base[$i];
        }

        $encoded = Bounce::encode($mixed);
        return TRUE;
    }

    static function decodeClicker($encoded, &$id) {
        $mixed = Bounce::decode($encoded);

        if (!is_numeric($mixed)) {
            return FALSE;
        }

        $mixed = str_split($mixed);
        $nid = "";

        for($i=0; $i<count($mixed);$i=$i+2) {
            $nid .= $mixed[$i];
        }

        $id = $nid;
        return TRUE;
    }

    static function encode($id) {
        return self::my_base_convert($id, 10, 62);
    }

    static function decode($code) {
        return self::my_base_convert($code, 62, 10);
    }

    static function my_base_convert ($numstring, $frombase = 62, $tobase = 10) {
       $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
       $tostring = substr($chars, 0, $tobase);

       $length = strlen($numstring);
       $result = '';
       for ($i = 0; $i < $length; $i++) {
           $number[$i] = strpos($chars, $numstring{$i});
       }
       do {
           $divide = 0;
           $newlen = 0;
           for ($i = 0; $i < $length; $i++) {
               $divide = $divide * $frombase + $number[$i];
               if ($divide >= $tobase) {
                   $number[$newlen++] = (int)($divide / $tobase);
                   $divide = $divide % $tobase;
               } elseif ($newlen > 0) {
                   $number[$newlen++] = 0;
               }
           }
           $length = $newlen;
           $result = $tostring{$divide} . $result;
       }
       while ($newlen != 0);
       return $result;
    }

    static function debug_request_log() {
        $data = PHP_EOL . "SERVER::" . PHP_EOL;
        foreach ($_SERVER as $key => $value) {
            if (stristr($key,'http_') !== FALSE) {
                $data .= "$key: $value\n";
            }
        }
        $data .= PHP_EOL . "COOKIE::" . PHP_EOL;
        foreach ($_COOKIE as $key => $value) {
            $data .= "$key: $value\n";
        }
        file_put_contents('tracker.log', $data, FILE_TEXT|FILE_APPEND);
    }
}
