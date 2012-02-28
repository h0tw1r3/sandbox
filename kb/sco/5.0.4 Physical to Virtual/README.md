# Convert SCO OpenServer 5.0.4 Physical to Virtual

Various notes from moving a physical SCO OpenServer 5.0.4 install to a virtual machine under VMWare and patching to current.  All steps below verified working on local vm installation.

## Pre-Virtualization Setup

**TODO** detailed steps

1. Add Buslogic SCSI driver to kernel.  Add to */etc/conf/cf.d/mscsi*

        blc        Sdsk        0        0        0        0
        
2. Shutdown, remove drive from machine.
3. Attach drive to VM host machine.
4. Create New Virtual Machine, add disk from physical disk.  Quit, **Do not start vm**.
6. Use _vmware-vdiskmanager.exe_ to copy physcial disk to virtual disk.
7. Boot vm, at bootloader type: defbootstr hd=Sdsk Sdsk=blc(0,0,0,0)
8. Relink kernel to set boot drive as default.

## Fix Installation

**WARNING** this section is job specific.  The general rule is to back out all supplements returning the install to a prestine (known) state.  Supplements *must* be removed in the same order they were installed (information _can_ be obtained using the _custom_ command).

    scoadmin software

1. Remove existing supplements
2. Select 'SCO OpenServer Enterprise System (var 5.0.4p)'
3. Software -> Verify Software
4. Select 'Normal system state (Thorough)'
5. Select 'Fix Discrepancies'

        shutdown -i6 -g0 -y

6. Disable tapebackup cron job (root)

## Setup Network

A working network and internet access is needed throught this document.  I recommend removing any adapters currently configured, rebooting, and the adding the new adapter.

1. Add network adapter

        /etc/netconfig

    Hardware -> Add new LAN adapter
    Select *AMD PCNet-PCI Adapter Compatible*
    Exit

2. Add default route

        mkdir -p /usr/internet/etc/sco_ip
        echo 'net default 10.1.1.1' >> /usr/internet/etc/sco_ip/routes

3. Add DNS server (do multiple times for additional servers)

        echo 'nameserver 10.1.1.1' >> /etc/resolv.conf

4. Reboot to test default route setting

        shutdown -i6 -g0 -y

5. Verify default route

        netstat -rn

## Install Release Supplement

Follow this specific order.  SoftMgr *must* be installed first as it patches the installer (custom).

    mkdir rs504c && cd rs504c
    rftp -m -bh ftp.sco.com /pub/openserver5/rs504c "VOL.000.000 VOL.001.000 VOL.002.000 VOL.003.000"
    custom -i -p SCO:SoftMgr -z `pwd`
    custom -i -p SCO:Supplement -z `pwd`
    custom -i -p SCO:XDrivers_Supp -z `pwd`
    shutdown -i6 -g0 -y

## Install Supplements

All supplements require rs504c.

1. oss469d: Core OS and Networking _(supersedes oss485a)_

        mkdir oss496d && cd oss469d
        rftp -mbh ftp.sco.com /pub/openserver5/oss469d "VOL.000.000 VOL.001.000"
        custom -i -p SCO:SLS -z `pwd`
        custom -x -V thorough
        shutdown -i6 -g0 -y

2. <strike>oss470a: Intel Pentium Erratum</strike> _(obsolete)_

3. <strike>oss471f: Intel Pentium Microcode Driver</strike> _(deprecated no replacement available)_

4. <strike>oss476a: Floppy Driver</strike> _(?superseded by oss615a?)_

5. oss478a: ATAPI (IDE) Driver _(requires floppy, ?superseded by oss615a?)_

        cd /tmp && rftp -m -bh ftp.sco.com /pub/openserver5 "oss478a.Z"
        zcat oss478a.Z | dd of=/dev/fd0135ds18
        /usr/bin/installpkg
        
6. oss480a: HTFS

        rftp -m -bh ftp.sco.com /pub/openserver5 "oss480a.Z"
        uncompress oss480a.Z && ln -sf `pwd`/oss480a /tmp/VOL.000.000
        custom -i -p SCO:link -z /tmp/
        shutdown -i6 -g0 -y

7. oss481a: SCO OpenServer SNMP

        rftp -m -bh ftp.sco.com /pub/openserver5 "oss481a"
        ln -sf `pwd`/oss481a /tmp/VOL.000.000
        custom -i -p SCO:tcp -z /tmp/

8. <strike>oss485a: OpenServer Memory</strike> _(superseded by oss469d)_

9. oss496a: Real Time clock

        rftp -mbh ftp.sco.com /pub/openserver5 "oss496a.Z"
        uncompress oss496a.Z && ln -sf `pwd`/oss496a /tmp/VOL.000.000
        custom -i -p SCO:SLS -z /tmp/
        shutdown -i6 -g0 -y

10. oss601a: Year 2000 Supplement _(must preceed oss465c)_

        mkdir oss601a && cd oss601a
        rftp -mbh ftp.sco.com /pub/openserver5/oss601a/ "VOL.000.000 VOL.001.000 VOL.002.000 VOL.003.000 VOL.004.000"
        custom -i -p SCO:SoftMgr -z `pwd`
        custom -i -p SCO:SLS -z `pwd`
        shutdown -i6 -g0 -y

11. oss465c: Scomail _(requires oss601a)_

        rftp -mbh ftp.sco.com /pub/openserver5/ "oss465c"
        ln -sf `pwd`/oss465c /tmp/VOL.000.000
        custom -i -p SCO:oss465c -z /tmp/

12. oss602b: Virtual Disk Manager

        rftp -mbh ftp.sco.com /pub/openserver5/ "oss602b"
        ln -sf `pwd`/oss602b /tmp/VOL.000.000
        custom -i -p SCO:oss602b -z /tmp/

16. oss605a: NetBIOS Drivers

        rftp -mbh ftp.sco.com /pub/openserver5/ "oss605a"
        ln -sf `pwd`/oss605a /tmp/VOL.000.000
        custom -i -p SCO:tcp -z /tmp/

17. oss615a: SCO Merge 5 Kernel Update _(requires oss601a)_

        rftp -mbh ftp.sco.com /pub/openserver5/ "oss615a"
        ln -sf `pwd`/oss615a /tmp/VOL.000.000
        custom -i -p SCO:OSS615A -z /tmp/

18. <strike>oss630a: OpenServer 5 UDK Socket</strike> _(requires OSRcompat)_

19. oss642a: Cron Supplement

        rftp -mbh ftp.sco.com /pub/openserver5/ "oss642a"
        ln -sf `pwd`/oss642a /tmp/VOL.000.000
        custom -i -p SCO:OSS642a -z /tmp/

20. oss644b: Reboot

        rftp -mbh ftp.sco.com /pub/openserver5 "oss644b"
        ln -sf `pwd`/oss644b /tmp/VOL.000.000
        custom -i -p SCO:OSS644B -z /tmp/
        shutdown -i6 -g0 -y

21. oss646c: Execution Environment

        mkdir oss646c && cd oss646c
        rftp -mbh ftp.sco.com /pub/openserver5/oss646c "VOL.000.000 VOL.000.001 VOL.000.002 VOL.000.003 VOL.000.004 VOL.000.005 VOL.000.006 VOL.000.007 VOL.000.008 VOL.000.009 VOL.000.010"
        custom -i -p SCO:OSS646 -z `pwd`
        shutdown -i6 -g0 -y

22. gwxlibs-2.1.0Ba: Supplemental Graphics, Web, and X11 Libraries _(requires oss646c, supercedes oss631)_

    Officially this package is only supported on 5.0.6+.  It is required by **most** 5.0.6+ compatible packages, and was officially included in 5.0.7.  Although the SPS indicates it _will_ work (albeit unsupported) on 5.0.4, changes to _/ibin/sh_ in 5.0.6+ prevent it from installing cleanly.  The solution is quite simple, and fully compatible with all Supplements and Skunkware packages.  Install _bash_ and replace _/ibin/sh_ with a symlink to _/bin/bash_.

        mkdir bash-2.03 && cd bash-2.03
        rftp -mbh ftp2.sco.com /pub/skunkware/osr5/shells/bash/ "bash-2.03-VOLS.tar"
        tar -xf bash-2.03-VOLS.tar
        custom -i -p SKUNK98:Bash -z `pwd`
        ln -sf /bin/bash /ibin/sh
        cd ..
        mkdir gwxlibs-2.1.0Ba && cd gwxlibs-2.1.0Ba
        rftp -mbh ftp.sco.com /pub/openserver5/opensrc/gwxlibs-2.1.0Ba "gwxlibs210Ba_vol.tar"
        tar -xf gwxlibs210Ba_vol.tar
        custom -i -p SCO:GWXLIBS -z `pwd`

23. gnutools-5.07Kj: GNU Development Tools

        mkdir gnutools-5.0.7Kj && cd gnutools-5.0.7Kj
        rftp -mbh ftp.sco.com /pub/openserver5/opensrc/gnutools-5.0.7Kj/ "VOL.000.000 VOL.000.001 VOL.000.002 VOL.000.003"
        custom -i -p SCO:gnutools -z `pwd`

## Setup GNU/Skunkware Environment

1. Install setup_gnu

    3rd party script makes installing SCO or Skunkware packages much quicker.
    The first execution may download and install _wget_ package.

        curl http://www.aljex.com/bkw/sco/setup_gnu | tr -d '\r' > /setup_gnu && chmod 750 /setup_gnu

2. Edit defaults

    * **/etc/default/login**

      Extend default path(s) to include GNU and local executables.

            sed -e 's/^\([SU]*\)PATH=\(.*\)/\1PATH=\2:\/usr\/gnu\/bin:\/usr\/local\/bin/' \
                -e 's/^SUPATH=\(.*\)/SUPATH=\1:\/usr\/local\/etc/' < /etc/default/login > /tmp/tmp.$$ && \
            cat /tmp/tmp.$$ > /etc/default/login && rm /tmp/tmp.$$

    * **/etc/default/man**

      Append _/usr/local/man_ to _MANPATH_.

            sed -e 's/^MANPATH=\(.*\)/MANPATH=\1:\/usr\/local\/man/' < /etc/default/man > /tmp/tmp.$$ && \
            cat /tmp/tmp.$$ > /etc/default/man && rm /tmp/tmp.$$

    * **/etc/profile**

      Override PAGER and EDITOR with _better_ defaults.

            echo '[ -x /usr/local/bin/less ] && { PAGER="/usr/local/bin/less"; export PAGER; }' >> /etc/profile
            echo '[ -x /usr/local/bin/pico ] && { EDITOR="/usr/local/bin/pico"; export EDITOR; }' >> /etc/profile

4. Logout / Reboot

   You should re-log to apply the changes.  If you don't, _setup gnu_ will install an old copy of _wget_.  Alternatively you can reboot, which will also pick up the MANPATH (used by scohttp).

3. Skunkware Recommendations

    * **pico-3.96**: simple, 'notepad' type text editor
    * **less-332**: execellent replacement text pager
    * **gzip-1.2.4**: used to compress most tar archives
    * **sudo-1.5.6p5**: you dont log into root directly do you?
    * **top-3.5beta5**: display running processes
    * **lsof-4.51**: show process details
    * **pine-4.10**: good local mail reader

## SSH Server & Client

SCO provides an upgrade package for 5.0.6 which works very well with 5.0.4.  Use the easy installer to download and install.

    /setup_gnu ftp://ftp.sco.com/pub/openserver5/opensrc/openssh-4.2p1/openssh42p1_vol.tar

There is only one caveat.  The install script does not completely modify _/etc/tcp_, therefor sshd will never start.  Copy the following into /etc/tcp around line 280.

     		#
     		# Start the secure shell daemon if requested
     		#
     		if [ "x$SECURESHELL" = "xYES" -a -x /etc/sshd ]; then
     			#
     			# If this is the first time starting up and there
     			# are no server keys, create them now. The system
     			# administrator may well want to restore existing
     			# ones at some future point, but this will at
     			# least allow the SSH daemon to start up.
     			#
     			kmsg=0
    			KFILE=/etc/ssh/ssh_host_key
     			test -f $KFILE || {
     			  /usr/bin/ssh-keygen -t rsa1 -f $KFILE -N "" > /dev/null 2>&1
     			  kmsg=1
     			}
     			KFILE=/etc/ssh/ssh_host_rsa_key
     			test -f $KFILE || {
     			  /usr/bin/ssh-keygen -t rsa -f $KFILE -N "" > /dev/null 2>&1
     			  kmsg=1
     			}
     			KFILE=/etc/ssh/ssh_host_dsa_key
     			test -f $KFILE || {
     			  /usr/bin/ssh-keygen -t dsa -f $KFILE -N "" > /dev/null 2>&1
     			  kmsg=1
     			}
     
     			/bin/su root -c "/usr/bin/sd sshd < /dev/console > /dev/console 2>&1 &"
     
     			if [ $kmsg -eq 1 ]; then
     				echo "sshd(KEYS CREATED) \c"
     			else
     				echo "sshd \c"
     			fi
     		fi
     		# OPENSSH }

Upon restarting _/etc/tcp_ you should see _prngd_ and _sshd_ in the list of services spawned.

    /etc/tcp stop
    /etc/tcp start

## Time Syncronization

For virtual machines, you *should not* add the local clock as a fallback reference.

1. Remove the link to _xntpd_ that comes with 5.0.4.  Until you do, it will conflict with the much newer and more configurable Skunkware package to be installed.

        rm /etc/xntpd

2. Download, install, and enable latest ntpd.

        /setup_gnu  `/setup_gnu list | grep ntp | head -1`
        /etc/ntp enable

3. Edit _/etc/ntp.conf_

        driftfile /etc/ntp.drift
        server 0.north-america.pool.ntp.org
        server 1.north-america.pool.ntp.org
        server 2.north-america.pool.ntp.org
        server 3.north-america.pool.ntp.org

4. Start ntp

        /etc/ntp start

## Other

### Logging
Generally it's preferred to send all logs to a central server.  Add the following to _/etc/syslog.conf_, replacing _logserver_ with the syslog server hostname.  IP addresses do not work with stock syslog.

    *.*		@logserver

### Faster Boot
Default _boot:_ loader timeout is 60 seconds.  Edit _/etc/default/boot_, set _TIMEOUT=5_ and _AUTOBOOT=YES_.

### Mail

_TODO_
