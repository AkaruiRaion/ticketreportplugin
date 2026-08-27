<?php

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight('plugin_ticketreport', READ);

// Получаем ID выбранной группы из запроса
$groupsId = isset($_REQUEST['groups_id']) ? (int) $_REQUEST['groups_id'] : 0;

// Если группа выбрана, получаем список пользователей этой группы
if ($groupsId > 0) {
    $users = Group_User::getGroupUsers($groupsId);
    $user_list = [];
    foreach ($users as $user) {
        $user_id = $user['id'];
        $fullname = formatUserName($user_id, $user['name'], $user['realname'], $user['firstname']);
        $user_list[$user_id] = $fullname;
    }

    Dropdown::showFromArray('users_id', $user_list, [
        'value'     => 0,
        'emptylabel' => __('Выберите пользователя'),
        'display_emptychoice' => true,
        'display'   => true
    ]);
} else {
    User::dropdown([
        'name'     => 'users_id',
        'value'    => 0,
        'right'    => 'all',
        'width' => '50%',
        'comments' => false,
        'display'  => true
    ]);
}
