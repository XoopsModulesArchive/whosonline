<?php
function b_whosonline_show($options)
{
    global $xoopsDB, $xoopsUser;

    b_whosonline_update();

    $block = [];

    $block['title'] = _MB_WHOSONLINE_TITLE1;

    $myts = MyTextSanitizer::getInstance();

    $result = $xoopsDB->query('SELECT COUNT(*) FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE uid=0 AND online=1');

    [$guest_online_num] = $xoopsDB->fetchRow($result);

    $result = $xoopsDB->query('SELECT COUNT(*) FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE uid>0 AND online=1');

    [$member_online_num] = $xoopsDB->fetchRow($result);

    $who_online_num = $guest_online_num + $member_online_num;

    $block['content'] = '<div align="left">';

    if ($member_online_num > 3) {
        $result = $xoopsDB->query('SELECT uid, username FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE uid > 0 AND online=1', 3, 0);

        while (list($memuid, $memusername) = $xoopsDB->fetchRow($result)) {
            $block['content'] .= '<a href="' . XOOPS_URL . "/userinfo.php?uid=$memuid\">" . htmlspecialchars($memusername, ENT_QUOTES | ENT_HTML5) . '</a> ';
        }

        $block['content'] .= "<a href=\"javascript:openWithSelfMain('" . XOOPS_URL . '/modules/whosonline/popup/online.php?t=' . time() . "','Online',220,350);\">" . _MB_WHOSONLINE_MORE . '</a><br>';
    } elseif (0 != $member_online_num) {
        $first = 0;

        $result = $xoopsDB->query('SELECT uid, username FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE uid>0 AND online=1');

        while (list($memuid, $memusername) = $xoopsDB->fetchRow($result)) {
            if (0 != $first) {
                $block['content'] .= ' ';
            }

            $first = 1;
        }
    }

    if ($xoopsUser) {
        $block['content'] .= sprintf(_MB_WHOSONLINE_URLAS, $xoopsUser->getVar('uname'));

        $block['content'] .= '<IMG height=10 src="' . XOOPS_URL . '/modules/whosonline/images/icon_connect.gif"width=14> &nbsp;</div>';
    } else {
        $block['content'] .= '</div>';
    }

    if (1 == $options[0]) {
        $myID = '';

        if ($xoopsUser) {
            $myID = $xoopsUser->getVar('uid');
        }

        $mintime = time() - ($options[1] * 86400);

        $result = $xoopsDB->query('SELECT uid, username, time FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE uid>0 AND time>' . $mintime . ' ORDER BY time DESC', $options[2], 0);

        while (list($uid, $uname, $time) = $xoopsDB->fetchRow($result)) {
            if ($uid != $myID) {
                $lastvisit = b_whosonline_create($time);

                $block['content'] .= '<br><small><a href="' . XOOPS_URL . '/userinfo.php?uid=' . $uid . '">' . htmlspecialchars($uname, ENT_QUOTES | ENT_HTML5) . '</a><IMG height=10 src="' . XOOPS_URL . '/modules/whosonline/images/quest.gif" width=14><br>';

                $block['content'] .= $lastvisit . "</small>\n";
            }
        }
    }

    return $block;
}

function b_whosonline_create($date)
{
    $realtime = time() - $date;

    $lastvisit = '';

    $days = '';

    $hours = '';

    $mins = '';

    //echo $realtime;
    // how many days ago?
    if ($realtime >= 86400) { // if it's been more than a day
        $days = floor($realtime / (86400));

        $realtime -= (86400 * $days);
    }

    // how many hours ago?

    if ($realtime >= (3600)) {
        $hours = floor($realtime / (3600));

        $realtime -= (3600 * $hours);
    }

    // how many minutes ago?

    if ($realtime >= 60) {
        $mins = floor($realtime / 60);

        $realtime -= (60 * $mins);
    }

    // just a little precation, although I don't *think* mins will ever be 60...

    if (60 == $mins) {
        $mins = 0;

        $hours += 1;
    }

    if ($days > 1) {
        $lastvisit .= sprintf(_MB_WHOSONLINE_DAYS, $days);
    } elseif (1 == $days) {
        $lastvisit .= _MB_WHOSONLINE_1DAY;
    }

    if ($hours > 0) {
        if (1 == $hours) {
            $lastvisit .= _MB_WHOSONLINE_1HR;
        } else {
            $lastvisit .= sprintf(_MB_WHOSONLINE_HRS, $hours);
        }
    }

    if ($mins > 0) {
        if (1 == $mins) {
            $lastvisit .= _MB_WHOSONLINE_1MIN;
        } else {
            $lastvisit .= sprintf(_MB_WHOSONLINE_MINS, $mins);
        }
    }

    if (!$days && !$hours && !$mins) {
        $lastvisit .= sprintf(_MB_WHOSONLINE_SCNDS, $realtime);
    }

    $lastvisit .= _MB_WHOSONLINE_AGO;

    return $lastvisit;
}

function b_whosonline_edit($options)
{
    $form = _MB_WHOSONLINE_SLAST . '&nbsp;';

    if (1 == $options[0]) {
        $chk = ' checked';
    }

    $form .= "<input type='radio' name='options[]' value='1'" . $chk . '>&nbsp;' . _YES . '';

    $chk = '';

    if (0 == $options[0]) {
        $chk = ' checked';
    }

    $form .= "&nbsp;<input type='radio' name='options[]' value='0'" . $chk . '>' . _NO . '<br>';

    $form .= _MB_WHOSONLINE_MDAYS . "&nbsp;<input type='text' name='options[]' value='" . $options[1] . "'>&nbsp;" . _MB_WHOSONLINE_DAYS2 . "\n";

    $form .= '<br>' . _MB_WHOSONLINE_MMEM . "&nbsp;<input type='text' name='options[]' value='" . $options[2] . "'>&nbsp;" . _MB_WHOSONLINE_MEMS . "\n";

    return $form;
}

/*
* Function to update last seen table
*/
function b_whosonline_update()
{
    global $xoopsUser, $xoopsDB, $REMOTE_ADDR;

    $past = time() - 300; // anonymous records are deleted after 10 minutes
    $userpast = time() - 8640000; // user records idle for the past 100 days are deleted
    $xoopsDB->queryF('DELETE FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE uid=0 AND time < ' . $past . '');

    $xoopsDB->queryF('DELETE FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE time < ' . $userpast . '');

    $xoopsDB->queryF('UPDATE ' . $xoopsDB->prefix('lastseen') . ' SET online=0 WHERE time < ' . $past . '');

    $ip = $REMOTE_ADDR;

    if ($xoopsUser) {
        $uid = $xoopsUser->getVar('uid');

        $uname = $xoopsUser->getVar('uname');
    } else {
        $uid = 0;

        $uname = 'Anonymous';
    }

    $sql = 'SELECT count(*) FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE uid=' . $uid . '';

    if (0 == $uid) {
        $sql .= " AND ip='" . $ip . "'";
    }

    //echo $sql;

    $result = $xoopsDB->query($sql);

    [$getRowsNum] = $xoopsDB->fetchRow($result);

    if ($getRowsNum > 0) {
        $sql = 'UPDATE ' . $xoopsDB->prefix('lastseen') . ' SET time = ' . time() . ", ip='" . $ip . "', online=1 WHERE uid=" . $uid . '';

        if (0 == $uid) {
            $sql .= " AND ip='" . $ip . "'";
        }

        $xoopsDB->queryF($sql);
    } else {
        $sql = 'INSERT INTO ' . $xoopsDB->prefix('lastseen') . ' (uid, username, time, ip, online) VALUES (' . $uid . ", '" . $uname . "', " . time() . ", '" . $ip . "', 1)";

        $xoopsDB->queryF($sql);
    }
}
