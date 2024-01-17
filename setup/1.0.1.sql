ALTER TABLE task ADD group_id TEXT;
ALTER TABLE task ADD share INTEGER;
update user set group_id=user_id where group_id is null or group_id='';
update task set group_id=(select u.group_id from user u where u.user_id=user_ins) where group_id is null;