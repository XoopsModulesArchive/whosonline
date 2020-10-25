<?php
include '../../../mainfile.php';
xoops_header(false);

echo "</head><body>\n";
$isadmin = 0;
if ($xoopsUser) {
    echo '<div><b>' . _WHOSONLINE . "</b><br><br><table width='100%'>\n";

    if ($xoopsUser->isAdmin()) {
        $sql = 'SELECT uid, username, ip FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE online=1 ORDER BY time DESC';

        $isadmin = 1;
    } else {
        $sql = 'SELECT uid, username FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE uid>0 AND online=1 ORDER BY time DESC';
    }
} else {
    $sql = 'SELECT uid, username FROM ' . $xoopsDB->prefix('lastseen') . ' WHERE uid>0 AND online=1 ORDER BY time DESC';
}
$result = $xoopsDB->query($sql);
if (!$xoopsDB->getRowsNum($result)) {
    echo "<tr class='bg1'><td>" . _NOUSRONLINE . "</td></tr>\n";
} else {
    if (1 == $isadmin) {
        while (list($uid, $username, $ip) = $xoopsDB->fetchRow($result)) {
            echo "<tr class='bg1'><td>";

            if ($uid > 0) {
                echo "<a href='javascript:window.opener.location=\"" . XOOPS_URL . '/userinfo.php?uid=' . $uid . "\";window.close();'>$username</a>";
            } else {
                echo $xoopsConfig['anonymous'];
            }

            echo "</td><td>$ip</td></tr>\n";
        }
    } else {
        while (list($uid, $username) = $xoopsDB->fetchRow($result)) {
            echo "<tr class='bg1'><td><a href='javascript:window.opener.location=\"" . XOOPS_URL . '/userinfo.php?uid=' . $uid . "\";window.close();'>$username</a></td></tr>\n";
        }
    }

    echo "</table></div>\n";
}
echo "<div><input value='" . _CLOSE . "' type='button' onclick='javascript:window.close();'></div>\n";
xoops_footer();
