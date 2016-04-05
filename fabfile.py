import os
from tools.fablib import *
from fabric.api import task
from fabric.colors import red

"""
Base configuration
"""
env.project_name = 'kinseycon'       # name for the project.
env.hosts = ['localhost', ]

try:
    env.domain
except AttributeError:
    env.domain = 'vagrant.dev'


"""
Add HipChat info to send a message to a room when new code has been deployed.
"""
env.hipchat_token = ''
env.hipchat_room_id = ''


# Environments
@task
def production():
    """
    Work on production environment
    """
    env.settings    = 'production'
    env.hosts       = [os.environ['KINSEY_CON_PRODUCTION_SFTP_HOST'], ]    # ssh host for production.
    env.user        = os.environ['KINSEY_CON_PRODUCTION_SFTP_USER']    # ssh user for production.
    env.password    = os.environ['KINSEY_CON_PRODUCTION_SFTP_PASSWORD']    # ssh password for production.
    env.domain      = 'kinseycon.wpengine.com'
    env.port        = 2222


@task
def staging():
    """
    Work on staging environment
    """
    env.settings    = 'staging'
    env.hosts       = []    # ssh host for staging.
    env.user        = ''    # ssh user for staging.
    env.password    = ''    # ssh password for staging.
    env.domain      = ''
    env.port        = 2222

    print(red("This project does not have a staging environment configured!"))

try:
    from local_fabfile import  *
except ImportError:
    pass
