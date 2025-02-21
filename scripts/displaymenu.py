#!/usr/bin/env python

# Author: Stefan Saam, github@saams.de

#######################################################################
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#######################################################################

# Provides a menu for the display.
# It can be used by hardware-buttons. Please read the Wiki at https://github.com/outdoorbits/little-backup-box/wiki/02a.-Displaymenu.

import time
import sys
import os
import subprocess
import RPi.GPIO as GPIO

import lib_language
import lib_network
import lib_storage


class menu(object):

	def __init__(self,DISPLAY_LINES,setup):

		self.DISPLAY_LINES	= DISPLAY_LINES;
		self.__setup			= setup
		self.__lan				= lib_language.language()

		self.WORKING_DIR = os.path.dirname(__file__)

		self.const_MEDIA_DIR					= self.__setup.get_val('const_MEDIA_DIR')
		self.conf_DISP_FRAME_TIME				= self.__setup.get_val('conf_DISP_FRAME_TIME')
		self.conf_RSYNC_SERVER					= self.__setup.get_val('conf_RSYNC_SERVER')
		self.conf_RSYNC_PORT					= self.__setup.get_val('conf_RSYNC_PORT')
		self.conf_RSYNC_USER					= self.__setup.get_val('conf_RSYNC_USER')
		self.conf_RSYNC_PASSWORD				= self.__setup.get_val('conf_RSYNC_PASSWORD')
		self.conf_RSYNC_SERVER_MODULE			= self.__setup.get_val('conf_RSYNC_SERVER_MODULE')
		self.const_BUTTONS_CONFIG_FILE			= self.__setup.get_val('const_BUTTONS_CONFIG_FILE')
		self.const_BUTTONS_PRIVATE_CONFIG_FILE	= self.__setup.get_val('const_BUTTONS_PRIVATE_CONFIG_FILE')
		self.conf_MENU_BUTTON_COMBINATION		= self.__setup.get_val('conf_MENU_BUTTON_COMBINATION')
		self.conf_MENU_BUTTON_ROTATE			= self.__setup.get_val('conf_MENU_BUTTON_ROTATE')
		self.conf_MENU_BUTTON_BOUNCETIME 		= self.__setup.get_val('conf_MENU_BUTTON_BOUNCETIME')
		self.GPIO_MENU_BUTTON_EDGE_DETECTION 	= GPIO.RISING	if self.__setup.get_val('conf_MENU_BUTTON_EDGE_DETECTION') == 'RISING' else GPIO.FALLING
		self.GPIO_MENU_BUTTON_RESISTOR_PULL 	= GPIO.PUD_DOWN	if self.__setup.get_val('conf_MENU_BUTTON_RESISTOR_PULL') == 'DOWN' else GPIO.PUD_UP

		self.RCLONE_CONFIG_FILE					= f"{self.const_MEDIA_DIR}/{self.__setup.get_val('const_RCLONE_CONFIG_FILE')}"
		self.const_MENU_TIMEOUT_SEC				= self.__setup.get_val('const_MENU_TIMEOUT_SEC')

		self.buttonevent_timestamp = {}

		## menu-types:
		#
		# menu
		# item: action is a confirmitem or as shell
		# confirmitem: Asks for confirmation, defines the action
		# shell: action contains the shell-command-array, title is ignored
		# info: displays an information

		kill_backup_process	= ['sudo','pkill','-f',f'"{self.WORKING_DIR}/backup*"']
		start_backup_trunk	= ['sudo','python3',f'{self.WORKING_DIR}/backup.py']

		# local backups
		local_sources	= ['usb','internal','camera']
		local_targets	= ['usb','internal']

		self.MENU_BACKUP_LOCAL	= []
		for source in local_sources:
			for target in local_targets:
				self.MENU_BACKUP_LOCAL.append(
					{
					'type':		'item',
					'title':	self.__lan.l(f'box_menu_backup_mode_{source}') + '|' + self.__lan.l('box_menu_to') + '|' + self.__lan.l(f'box_menu_backup_mode_{target}'),
					'action':	self.create_shell_action([ kill_backup_process,start_backup_trunk + [source,target] ]),
					}
				)

		# cloud backups
		cloudservices	= []

		## rsyncserver
		if not (self.conf_RSYNC_SERVER =='' or self.conf_RSYNC_PORT =='' or self.conf_RSYNC_USER =='' or self.conf_RSYNC_PASSWORD =='' or self.conf_RSYNC_SERVER_MODULE ==''):
			cloudservices.append('cloud_rsync')

		## rclone services
		rclone_cloudservices	= subprocess.check_output('sudo rclone config show --config "{}" | grep "^\[.*\]$" | sed "s/^\[//" | sed "s/\]$//"'.format(self.RCLONE_CONFIG_FILE),shell=True).decode('UTF-8').strip().split('\n')
		for i in range(len(rclone_cloudservices)):
			rclone_cloudservices[i]	= f'cloud:{rclone_cloudservices[i]}'

		cloudservices			+= rclone_cloudservices

		self.MENU_BACKUP_CLOUD	= []

		for source in (local_sources + cloudservices):
			for target in (local_sources + cloudservices):

				# impossible combinations
				if source == target:
					continue
				if source == 'cloud_rsync':
					continue
				if source == 'camera' and target == 'cloud_rsync':
					continue
				if target == 'camera':
					continue
				if (not source in cloudservices) and (not target in cloudservices):
					continue

				sourceType, sourceCloudServiceName		= lib_storage.extractCloudService(source)
				targetType, targetCloudServiceName		= lib_storage.extractCloudService(target)

				if sourceType == 'cloud':
					sourceName	= sourceCloudServiceName
				else:
					sourceName	= self.__lan.l(f'box_menu_backup_mode_{sourceType}')
				if targetType == 'cloud':
					targetName	= targetCloudServiceName
				else:
					targetName	= self.__lan.l(f'box_menu_backup_mode_{targetType}')

				self.MENU_BACKUP_CLOUD.append(
					{
						'type':		'item',
						'title':	sourceName + '|' + self.__lan.l('box_menu_to') + '|' + targetName,
						'action':	self.create_shell_action([ kill_backup_process,start_backup_trunk + [source,target] ]),
					}
				)

		self.MENU_BACKUP	= [
			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_backup_local'),
				'action':	self.MENU_BACKUP_LOCAL,
			},

			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_backup_cloud'),
				'action':	self.MENU_BACKUP_CLOUD,
			},

			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_backup_stop'),
				'action':	self.create_confirmed_shell_action(self.__lan.l('box_menu_backup_stop'),[kill_backup_process]),
			},
		]

		self.MENU_NETWORK	= [
			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_ip'),
				'action':	[
								{
									'type':		'info',
									'title':	self.__lan.l('box_menu_ip'),
									'action':	'ip',
								}
							],
			},

			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_comitup_reset'),
				'action':	self.create_confirmed_shell_action(self.__lan.l('box_menu_comitup_reset'),[['sudo','comitup-cli','d']]),
			},

			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_vpn_stop'),
				'action':	self.create_confirmed_shell_action(self.__lan.l('box_menu_vpn_stop'),[['sudo','python3', f"{self.WORKING_DIR}/lib_vpn.py", 'stop']]),
			},
		]

		self.MENU_POWER	= [
			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_power_reboot'),
				'action':	self.create_confirmed_shell_action(self.__lan.l('box_menu_power_reboot'),[['sudo','python3',f'{self.WORKING_DIR}/lib_poweroff.py','reboot' ]]),
			},

			{
				'type':		'item',
				'title':	self.__lan.l('box_menu_power_shutdown'),
				'action':	self.create_confirmed_shell_action(self.__lan.l('box_menu_power_shutdown'),[['sudo','python3',f'{self.WORKING_DIR}/lib_poweroff.py','poweroff' ]]),
			},
		]

		self.MENU_MAIN	= [
			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_backup'),
				'action':	self.MENU_BACKUP,
			},

			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_network'),
				'action':	self.MENU_NETWORK,
			},

			{
				'type':		'menu',
				'title':	self.__lan.l('box_menu_system'),
				'action':	self.MENU_POWER,
			},

		]

		# define menu variables an (re-)set them
		self.reset()

		self.GPIO_init()

	def GPIO_init(self):
		GPIO.setmode(GPIO.BCM)
		GPIO.setwarnings(False)

		if self.conf_MENU_BUTTON_COMBINATION:
			if self.conf_MENU_BUTTON_COMBINATION.isnumeric():
				ButtonsConfigFile		= f"{self.WORKING_DIR}/{self.const_BUTTONS_CONFIG_FILE}"
				ButtonCombinationNumber	= int(self.conf_MENU_BUTTON_COMBINATION)
			elif self.conf_MENU_BUTTON_COMBINATION[0:1] == 'c':
				ButtonsConfigFile		= f"{self.const_MEDIA_DIR}/{self.const_BUTTONS_PRIVATE_CONFIG_FILE}"
				ButtonCombinationNumber	=  int(self.conf_MENU_BUTTON_COMBINATION[1:])

			if os.path.isfile(ButtonsConfigFile):
				ConfigLines	= []
				with open(ButtonsConfigFile,'r') as f:
					ConfigLines	= f.readlines()

				ConfigLineNumber	= 0
				for ConfigLine in ConfigLines:
					ConfigLine	= ConfigLine.strip()
					if ConfigLine:
						if ConfigLine[0:1] != '#':
							ConfigLineNumber	+= 1
							if ConfigLineNumber	== ButtonCombinationNumber:
								ConfigLine	= ConfigLine.split(':',1)[0]
								ButtonDefs	= ConfigLine.split(',')
								for ButtonDef in ButtonDefs:
									GPIO_PIN, ButtonFunction	= ButtonDef.split('=')
									GPIO_PIN	= int(GPIO_PIN)

									self.GPIO_config_button(GPIO_PIN,ButtonFunction)
									self.buttonevent_timestamp[GPIO_PIN] = 0

	def GPIO_config_button(self,GPIO_PIN,ButtonFunction):
		GPIO.setup(GPIO_PIN, GPIO.IN, pull_up_down = self.GPIO_MENU_BUTTON_RESISTOR_PULL)

		GPIO.remove_event_detect(GPIO_PIN)

		# rotate buttons
		if self.conf_MENU_BUTTON_ROTATE == 2:
			if ButtonFunction == 'up':
				ButtonFunction	= 'down'
			elif ButtonFunction == 'down':
				ButtonFunction	= 'up'
			elif ButtonFunction == 'left':
				ButtonFunction	= 'right'
			elif ButtonFunction == 'right':
				ButtonFunction	= 'left'

		# add events to buttons
		if ButtonFunction == 'up':
			GPIO.add_event_detect(GPIO_PIN, self.GPIO_MENU_BUTTON_EDGE_DETECTION, callback = self.move_up, bouncetime=self.conf_MENU_BUTTON_BOUNCETIME)
		elif ButtonFunction == 'down':
			GPIO.add_event_detect(GPIO_PIN, self.GPIO_MENU_BUTTON_EDGE_DETECTION, callback = self.move_down, bouncetime=self.conf_MENU_BUTTON_BOUNCETIME)
		elif ButtonFunction == 'left':
			GPIO.add_event_detect(GPIO_PIN, self.GPIO_MENU_BUTTON_EDGE_DETECTION, callback = self.move_left, bouncetime=self.conf_MENU_BUTTON_BOUNCETIME)
		elif ButtonFunction == 'right':
			GPIO.add_event_detect(GPIO_PIN, self.GPIO_MENU_BUTTON_EDGE_DETECTION, callback = self.move_right, bouncetime=self.conf_MENU_BUTTON_BOUNCETIME)

	def bouncing(self,channel):
		# GPIO bouncetime not always works as expected. This function filters bouncing.

		if channel == 0:
			self.buttonevent_timestamp[channel]	= time.time()
			return(True)

		if abs(time.time() - self.buttonevent_timestamp[channel]) >= self.conf_MENU_BUTTON_BOUNCETIME/1000:
			self.buttonevent_timestamp[channel]	= time.time()
			return(False)
		else:
			self.buttonevent_timestamp[channel]	= time.time()
			return(True)

	def __del__(self):
		GPIO.cleanup()

	def reset(self):
		self.MENU_LEVEL		= 0 # integer
		self.MENU			= []
		self.MENU_POS		= []
		self.MENU_SHIFT		= []
		self.HEAD_LINES	= 0

		self.LAST_INPUT_TIME	= time.time()

		# init basic menu
		self.MENU.append(self.MENU_MAIN) # points to the actually active menu level
		self.MENU_POS.append(0)
		self.MENU_SHIFT.append(0)

	def check_timeout(self):
		if abs(time.time() - self.LAST_INPUT_TIME) >= self.const_MENU_TIMEOUT_SEC:
			self.reset()
		else:
			self.LAST_INPUT_TIME	= time.time()

	def create_confirmed_shell_action(self,title,command):
		return([
			{
				'type':		'confirmitem',
				'title':	title,
				'action':	self.create_shell_action(command,title),
			},
		])

	def create_shell_action(self,command,title=''):
		return([
			{
				'type':		'shell',
				'title':	title,
				'action':	command,
			},
		]
		)

	def set_shift(self):
		display_active_line	= self.MENU_POS[self.MENU_LEVEL] + 1 + self.HEAD_LINES - self.MENU_SHIFT[self.MENU_LEVEL]
		if display_active_line <= self.HEAD_LINES:
			# shift up
			self.MENU_SHIFT[self.MENU_LEVEL] = self.MENU_POS[self.MENU_LEVEL]
		elif display_active_line > self.DISPLAY_LINES - self.HEAD_LINES:
			# shift down
			self.MENU_SHIFT[self.MENU_LEVEL] = self.HEAD_LINES + self.MENU_POS[self.MENU_LEVEL] + 1 - self.DISPLAY_LINES

	def get_INFO(self,action):
		if action == 'ip':
			IP		= lib_network.get_IP()
			STATUS	= self.__lan.l('box_cronip_online') if lib_network.get_internet_status() else self.__lan.l('box_cronip_offline')

			return([f"s=b:{IP}", f"s=b:{STATUS}"])

	def display(self,channel):
		self.check_timeout()

		self.set_shift()

		frame_time			= 0
		LINES 				= []
		n					= 0

		# define title
		self.HEAD_LINES = 0
		if self.MENU_LEVEL > 0:
			if self.MENU[self.MENU_LEVEL - 1][self.MENU_POS[self.MENU_LEVEL - 1]]['type'] == 'menu':
				HEAD_LINE = self.MENU[self.MENU_LEVEL - 1][self.MENU_POS[self.MENU_LEVEL - 1]]['title'].strip()
				if HEAD_LINE != '':
					self.HEAD_LINES = 1
					LINES	+= [f"s=h,u:{HEAD_LINE}"]

		# generate content
		for item in self.MENU[self.MENU_LEVEL]:

			if (n >= self.MENU_SHIFT[self.MENU_LEVEL]) and (n < self.DISPLAY_LINES + self.MENU_SHIFT[self.MENU_LEVEL] - self.HEAD_LINES):

				# Title can be combined by more than one part to translate separately.
				# Parts are separated by "|"
				TITLE = ''
				titleparts = self.MENU[self.MENU_LEVEL][n]['title'].split('|')
				for titlepart in titleparts:
					TITLE = "{}{} ".format(TITLE,titlepart)

				# confirm item
				if self.MENU[self.MENU_LEVEL][n]['type'] == 'confirmitem':
					LINES	+= [f"s=b:{self.__lan.l('box_menu_confirm')}", f"s=b:{TITLE}", "s=h:{}".format(self.__lan.l('box_menu_yes'))]
					break

				elif self.MENU[self.MENU_LEVEL][n]['type'] == 'shell':
					for command in self.MENU[self.MENU_LEVEL][n]['action']:
						subprocess.Popen(command, shell=False)

					self.reset()
					LINES	= []
					break

				elif self.MENU[self.MENU_LEVEL][n]['type'] == 'info':

					LINES += [f"s=h:{TITLE}"] + self.get_INFO(self.MENU[self.MENU_LEVEL][n]['action'])
					frame_time = self.conf_DISP_FRAME_TIME * 4

					self.reset()
					break

				# menu or item
				elif self.MENU[self.MENU_LEVEL][n]['type'] in ['menu','item']:
					line	= TITLE

					if self.MENU[self.MENU_LEVEL][n]['type'] == 'menu':
						line = f"+ {line}"

					if n == self.MENU_POS[self.MENU_LEVEL]:
						style	= 's=h'
					else:
						style	= 's=b'

					LINES+= [f"{style}:{line}"]
				else:
					print('No known menu type: {}'.format(self.MENU[self.MENU_LEVEL][n]['type']))

			n += 1

		LINES_Str	= "' '".join(LINES)
		LINES_Str	= f"'{LINES_Str}'"
		os.system(f"python3 {self.WORKING_DIR}/lib_display.py 'set:clear,time={frame_time}' {LINES_Str}")

		self.buttonevent_timestamp[channel]	= time.time()

	def move_down(self,channel):
		if not self.bouncing(channel):

			if len(self.MENU[self.MENU_LEVEL]) > (self.MENU_POS[self.MENU_LEVEL] + 1):
				self.MENU_POS[self.MENU_LEVEL] += 1
			else:
				self.MENU_POS[self.MENU_LEVEL] = 0

			self.display(channel)

	def move_up(self,channel):
		if not self.bouncing(channel):
			if self.MENU_POS[self.MENU_LEVEL] > 0:
				self.MENU_POS[self.MENU_LEVEL] += -1
			elif (len(self.MENU[self.MENU_LEVEL]) > 0):
				self.MENU_POS[self.MENU_LEVEL] = len(self.MENU[self.MENU_LEVEL]) - 1
			self.display(channel)

	def move_right(self,channel):
		if not self.bouncing(channel):
			menu_new	= self.MENU[self.MENU_LEVEL][self.MENU_POS[self.MENU_LEVEL]]['action']
			self.MENU_LEVEL += 1

			# replace or append next menu level
			if len(self.MENU) >= self.MENU_LEVEL + 1:
				self.MENU[self.MENU_LEVEL] = menu_new
				self.MENU_POS[self.MENU_LEVEL] = 0
				self.MENU_SHIFT[self.MENU_LEVEL] = 0
			else:
				self.MENU.append(menu_new)
				self.MENU_POS.append(0)
				self.MENU_SHIFT.append(0)

			self.display(channel)

	def move_left(self,channel):
		if not self.bouncing(channel):
			if self.MENU_LEVEL > 0:
				self.MENU_LEVEL += -1
			self.display(channel)

##debug
#if __name__ == "__main__":

	#import lib_setup

	#setup=lib_setup.setup()

	#menuobj	= menu(10,setup)

	#menuobj.move_right(27)#debug
	#time.sleep (0.5)
	#menuobj.move_right(27)#debug
	#time.sleep (0.5)
	#menuobj.move_right(27)#debug
	#time.sleep (0.5)
	#menuobj.move_right(27)#debug

	#menuobj.move_down(0)#debug
	#menuobj.move_down(0)#debug
	#menuobj.move_right(0)#debug
	#menuobj.move_down(0)#debug
	##menuobj.move_right(0)#debug
	##menuobj.move_right(0)#debug
	#time.sleep(20)
	#print('End.')
