# This is a cron job that will detect PhpServer.php failure and restarts it automatically.
# Add this line to crontab -e, so the py script will be ran every munite:
#     * * * * * /usr/bin/python /home/ubuntu/yaoliu/thrift/tutorial/tagtalk_dev/detect_failure_cron.py
# A very simple log will be stored in /tmp/server_health_log.txt
# Also, if detected failure and restarted, will send an email to administrators.

import socket
import commands
import subprocess
import sys
import os
import smtplib
from email.mime.text import MIMEText
from time import gmtime, strftime

f = open('/tmp/server_health_log.txt', 'a')

time = strftime("%Y-%m-%d %H:%M:%S", gmtime()) + " UTC"

# list all currently running php processes.
ps_out = commands.getstatusoutput("ps -o cmd= -C php")[1]

# if no php process with running command contains 'PhpServer.php', server is dead.
if (ps_out.find("PhpServer.php") == -1):
  # Restart server and write log.
  f.write('dead:      ' + time + '\n')
  p = subprocess.Popen(['/usr/bin/php', os.path.expanduser('/home/ubuntu/yaoliu/thrift/tutorial/tagtalk_dev/PhpServer.php')])
  f.write('restarted: ' + time + '\n')
  
  # Send the informing email via our own SMTP server
  sender = "support@tagtalk.co" 
  receiver = ["s810011@gmail.com", "s810434@gmail.com", "yliu182@gmail.com", "zhangzhaoyu1985@gmail.com", "qing0325@gmail.com", "soybean217@gmail.com", "jerrychenw@gmail.com"] 
  msg = MIMEText("Anyone messing around our server (physical address: " + socket.gethostbyname(socket.gethostname()) + ")? >.^\n\nThe cron job that runs every munite detected our server process is dead on: " + time + "\n\nIt just restarted the server, however please still check server health ASAP!")
  msg['Subject'] = "TagTalk Server DEAD and RESTARTED!!!";
  msg['From'] = sender 
  msg['To'] = ",".join(receiver) 
  smtp = smtplib.SMTP('localhost')
  smtp.sendmail(sender, receiver, msg.as_string())
  smtp.quit()

  # Backup Restart Option (1)
  # pid = os.fork()
  # if pid == 0: # new process
  #   os.system("nohup /usr/bin/php /home/ubuntu/yaoliu/thrift/tutorial/tagtalk_dev/PhpServer.php &")
  #
  # Backup Restart Option (2)
  # php_out = commands.getstatusoutput("nohup /usr/bin/php /home/ubuntu/yaoliu/thrift/tutorial/tagtalk_dev/PhpServer.php &")[1]

f.close()
exit()

