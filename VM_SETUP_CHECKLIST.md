# VM Setup Checklist

## Pre-Setup Information
- **Date Started**: _______________
- **Student Name**: _______________
- **Instructor**: _______________

---

## ✅ Phase 1: VM Creation
- [ ] Created new VM in VirtualBox/VMware
  - VM Name: _______________
  - OS: Ubuntu 22.04 LTS
  - RAM Allocated: _______________ MB
  - CPUs: _______________
  - Storage: Used existing .vmdk file

- [ ] Network Configuration
  - Network Type: [ ] Bridged [ ] NAT
  - If NAT, Port Forwarding Set: [ ] Yes [ ] No

---

## ✅ Phase 2: OS Installation
- [ ] Installed Ubuntu from ISO
  - Username: _______________
  - Hostname: _______________
  - Installation Date: _______________

- [ ] System Updated
  ```bash
  sudo apt update && sudo apt upgrade -y
  ```
  - Date Updated: _______________

- [ ] VM IP Address Obtained
  - **IP Address**: _______________
  - Command used: `ip addr show` OR `ifconfig`

---

## ✅ Phase 3: LAMP Stack Setup
- [ ] Transferred lamp_lab.sh to VM
  - Method used: [ ] Git Clone [ ] SCP [ ] Shared Folder
  - Date: _______________

- [ ] Made script executable
  ```bash
  chmod +x lamp_lab.sh
  ```

- [ ] Executed lamp_lab.sh
  ```bash
  sudo ./lamp_lab.sh
  ```
  - Execution Status: [ ] Success [ ] Failed
  - Date/Time: _______________
  - Any errors? _______________

---

## ✅ Phase 4: Verification
- [ ] Accessed http://<VM-IP>/
  - Status: [ ] Working [ ] Not Working
  - Screenshot taken: [ ] Yes [ ] No

- [ ] Accessed http://<VM-IP>/adminer/
  - Status: [ ] Working [ ] Not Working
  - Database login successful: [ ] Yes [ ] No

- [ ] Database Credentials (from lamp_lab.sh):
  - Database Name: `studentdb`
  - Username: `student`
  - Password: `Password123!`

---

## ✅ Phase 5: SSH Access (Optional but Recommended)
- [ ] SSH Server installed
  ```bash
  sudo apt install -y openssh-server
  sudo systemctl status ssh
  ```

- [ ] Can SSH from host machine
  ```bash
  ssh username@<VM-IP>
  ```
  - Status: [ ] Working [ ] Not Working

---

## 📝 Changes Made

| Date | Change Description | Reason | File/Command |
|------|-------------------|--------|--------------|
| | | | |
| | | | |
| | | | |

---

## 🐛 Issues Encountered

| Date | Issue | Solution | Reference |
|------|-------|----------|-----------|
| | | | |
| | | | |

---

## 📸 Screenshots Location
- Screenshot folder path: _______________

---

## ✅ Ready for Website Development
- [ ] All checks passed
- [ ] VM IP documented
- [ ] Database accessible
- [ ] Apache serving pages
- [ ] Ready to create 3-page website

**Date Completed**: _______________
