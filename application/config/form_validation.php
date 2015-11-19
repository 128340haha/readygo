<?php
$config=array(
	'login'	=>	array(
		array(
				'field'=>'username',
				'label'=>'用户名',
				'rules'=>'required'
		),
		array(
				'field'=>'password',
				'label'=>'密码',
				'rules'=>'required'
		)
	),
	'admin'	=>	array(
			array(
				'field'=>'admin',
				'label'=>'用户名',
				'rules'=>'required|alpha_dash'	//
			),
			array(
				'field'=>'password',
				'label'=>'密码',
				'rules'=>'required'
			)
	),
	'reg'	=>	array(
		array(
				'field'=>'username',
				'label'=>'用户名',
				'rules'=>'required|min_length[4]|max_length[30]'	//|alpha_dash
		),
		array(
				'field'=>'password',
				'label'=>'密码',
				'rules'=>'required|min_length[6]|max_length[30]'
		),
	),
	'uinfo'	=>	array(
			array(
				'field'=>'mail',
				'label'=>'邮箱',
				'rules'=>'valid_email'
			),
			array(
				'field'=>'phone',
				'label'=>'手机',
				'rules'=>'numeric|exact_length[11]'
			),
			array(
				'field'=>'age',
				'label'=>'年龄',
				'rules'=>'numeric'
			),
			array(
				'field'=>'stature',
				'label'=>'身高',
				'rules'=>'numeric'
			),
			array(
				'field'=>'weight',
				'label'=>'体重',
				'rules'=>'numeric'
			),
			array(
				'field'=>'sex',
				'label'=>'性别',
				'rules'=>'numeric|exact_length[1]'
			)
	),
	'email'	=>	array(
		array(
				'field'=>'emailaddress',
				'label'=>'EmailAddress',
				'rules'=>'required|valid_email'
		),
		array(
				'field'=>'name',
				'label'=>'Name',
				'rules'=>'required|alpha'
		)
	),
	'bind'	=>	array(
		array(
				'field'=>'code',
				'label'=>'设备编码',
				'rules'=>'required|exact_length[12]|alpha_numeric'
		),
		array(
				'field'=>'salt',
				'label'=>'校验码',
				'rules'=>'required|exact_length[6]|alpha_numeric'
		),
		array(
				'field'=>'device_name',
				'label'=>'设备名称',
				'rules'=>'required'
		)
	),
	'reset_pass'	=>	array(
		array(
				'field'=>'password',
				'label'=>'密码',
				'rules'=>'required|min_length[6]|max_length[30]'
		),
		array(
				'field'=>'ckpwd',
				'label'=>'确认密码',
				'rules'=>'required'
		)
	)
);


?>