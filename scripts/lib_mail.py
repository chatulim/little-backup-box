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

import lib_log
import lib_setup

import os
import sys

import smtplib, ssl
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

class mail(object):

	def __init__(self):
		self.WORKING_DIR = os.path.dirname(__file__)

		self.log=lib_log.log()

		self.setup	= lib_setup.setup()
		self.conf_SMTP_SERVER 		= self.setup.get_val('conf_SMTP_SERVER')
		self.conf_SMTP_PORT 		= self.setup.get_val('conf_SMTP_PORT')
		self.conf_MAIL_SECURITY 	= self.setup.get_val('conf_MAIL_SECURITY')
		self.conf_MAIL_USER 		= self.setup.get_val('conf_MAIL_USER')
		self.conf_MAIL_PASSWORD 	= self.setup.get_val('conf_MAIL_PASSWORD')
		self.conf_MAIL_FROM 		= self.setup.get_val('conf_MAIL_FROM')
		self.conf_MAIL_TO 			= self.setup.get_val('conf_MAIL_TO')

		self.conf_MAIL_SECURITY 	= self.setup.get_val('conf_MAIL_SECURITY')
		self.const_MAIL_TIMEOUT 	= self.setup.get_val('const_MAIL_TIMEOUT')
		self.conf_MAIL_HTML 		= self.setup.get_val('conf_MAIL_HTML')

	def mail_configured(self):
		return(self.conf_SMTP_SERVER and self.conf_SMTP_PORT and self.conf_MAIL_SECURITY and self.conf_MAIL_USER and self.conf_MAIL_PASSWORD and self.conf_MAIL_FROM and self.conf_MAIL_TO)

	def sendmail(self,Subject,TextPlain,TextHTML=''):
		if not self.mail_configured():
			self.log.message('Mail not fully configured.')
			return

		if self.conf_MAIL_SECURITY == 'SSL':
			# SSL
			context = ssl.create_default_context()
			try:
				server = smtplib.SMTP_SSL(self.conf_SMTP_SERVER, self.conf_SMTP_PORT, context=context, timeout=self.const_MAIL_TIMEOUT)
				server.login(self.conf_MAIL_USER, self.conf_MAIL_PASSWORD)
			except Exception as e:
				self.log.message(f"Error sending mail: {e}")

		else:
			# STARTTLS
			try:
				server = smtplib.SMTP(self.conf_SMTP_SERVER, self.conf_SMTP_PORT, timeout=self.const_MAIL_TIMEOUT)
				server.ehlo()
				server.starttls(context=context)
				server.ehlo()
				server.login(self.conf_MAIL_USER, self.conf_MAIL_PASSWORD)

			except Exception as e:
				self.log.message(f"Error sending mail: {e}")

		if self.conf_MAIL_HTML:
			MailContent = MIMEMultipart("alternative")
			MailContent["Subject"] = Subject
			MailContent["From"] = self.conf_MAIL_FROM
			MailContent["To"] = self.conf_MAIL_TO

			MailContentPlain	= MIMEText(TextPlain, "plain")
			MailContent.attach(MailContentPlain)

			if TextHTML:
				MailContentHTML		= MIMEText(TextHTML, "html")
				MailContent.attach(MailContentHTML)

			try:
				server.sendmail(self.conf_MAIL_USER, self.conf_MAIL_TO, MailContent.as_string())
			except Exception as e:
				self.log.message(f"Error sending mail: {e}")

		else: # Plain text mail
			MailContent = f"""\
Subject: {Subject}

{TextPlain}
"""
			try:
				server.sendmail(self.conf_MAIL_USER, self.conf_MAIL_TO, MailContent)
			except Exception as e:
				self.log.message(f"Error sending mail: {e}")

		try:
			server.quit()
		except Exception as e:
				self.log.message(f"Error sending mail: {e}")




if __name__ == "__main__":

	try:
		Subject		= sys.argv[1]
	except:
		Subject		= 'Mail from your Little Backup Box'

	try:
		TextPlain	= sys.argv[2]
	except:
		TextPlain	= 'Plain text missing'

	try:
		TextHTML	= sys.argv[3]
	except:
		TextHTML	= ''

	mailer = mail()
	mailer.sendmail(Subject,TextPlain,TextHTML)



