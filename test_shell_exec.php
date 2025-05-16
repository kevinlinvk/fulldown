<?php
echo '<pre>';
echo "shell_exec 测试：\n";
$output = shell_exec('echo hello_shell_exec');
if ($output === null) {
    echo "shell_exec 被禁用或无权限\n";
} else {
    echo "shell_exec 正常，输出：\n";
    echo $output;
}
echo '</pre>';
?>