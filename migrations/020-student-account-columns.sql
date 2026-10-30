ALTER TABLE tblstudents
  ADD COLUMN GuardianEmail VARCHAR(150) NULL AFTER NextOfKinPhone,
  ADD COLUMN StudentUsername VARCHAR(100) NULL AFTER GuardianEmail,
  ADD COLUMN StudentPassword VARCHAR(255) NULL AFTER StudentUsername;
