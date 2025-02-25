#!/bin/bash

# Параметры подключения
HOST='d91955kx.beget.tech'
USER='d91955kx_'
PASS='Pol1nka001'  # Замените на актуальный пароль
REMOTE_DIR='/public_html'
LOCAL_DIR='C:\Users\polin\OneDrive\Desktop\dbcollege'

# Команда синхронизации
lftp -f "
open $HOST
user $USER $PASS
mirror --reverse --delete $LOCAL_DIR $REMOTE_DIR
quit
"
