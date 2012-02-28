# SCO 5.0.6 Install on QEMU (KVM)

Notes taken from installing SCO OpenServer 5.0.6 from scratch on QEMU.

## Environment and Materials

Assume latest pre-packaged bleeding vesions.

1. Debian
2. libvirt
3. QEMU
4. SCO Openserver 5.0.6 ISO Image
5. LSI Logic BIOS
6. LSI SCO Driver BTLD, 4.11.03

## Setup VM

TODO

## Install OS

TODO

## Patch OS

### Change Network Device

The pcnet network device does not work well in my experience.  The e1000 driver is preferred but it does not come included with SCO 5.0.6.

    mkdir -p /tmp/drivers/network/eeG && cd /tmp/drivers/network/eeG
    rftp -mbh ftp.sco.com /pub/openserver5/drivers/OSR506/network/eeG "VOL.000.000 eeG.readme"
    custom -i -p SCO:eeG -z `pwd`
    shutdown -i0 -g0 -y

Edit the domain in virsh, change the network interface model type to 'e1000' and restart the domain.  Boot into single user mode.

    netconfig

Remove _AMD PCNet-PCI Adapter_
Add new LAN adapter.  Intel(R) PRO/1000 should be auto detected.

    shutdown -i6 -g0 -y

### Install Release Supplement

    mkdir /tmp/supplements
    cd /tmp/supplements
    mkdir rs506a && cd rs506a
    rftp -mbh ftp.sco.com /pub/openserver5/rs506a "rs506a.tar rs506a.txt"
    rftp -mbh ftp.sco.com /pub/openserver5/drivers/OSR506/btld/slha "slha.btld"
    custom -i -p SCO:SoftMgr -z `pwd`
    # 'i'gnore the kernel link error, it is a known problem
    custom -i -p SCO:rs506a -z `pwd`
    # loopback mount lsi driver disk
    marry -a `pwd`/slha.btld
    mount /dev/marry`pwd`/slha.btld /mnt
    # reinstall 'slha' driver, kernel should build O.K. now.
    btldinstall /mnt
    umount /mnt && marry -d `pwd`/slha.btld
    custom -i -p SCO:SendMail -z `pwd`
    # don't bother with usb for - some reason broke kernel linking later.
    # custom -i -p SCO:usb -z `pwd` 
    shutdown -i6 -g0 -y

### Install Supplements

## Credits

[Tony Lawrence][11]

## Links

[1]: http://partnerweb.vmware.com/GOSIG/SCO_OpenServer_5.html
[2]: ftp://ftp.sco.com/pub/openserver5/507/oss661a.ltr
[3]: http://fixunix.com/sco/89888-sco-os-5-0-7-qemu.html
[4]: http://www.lsi.com/support/products/Pages/LSI53C895A.aspx
[5]: ftp://ftpput.sco.com/pub/tools/patchck.tar.Z
[11]: http://aplawrence.com/

