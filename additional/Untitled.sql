delete from account_insights_actions;
delete from account_insights;
delete from accounts;
delete from campaigns_insights_actions;
delete from campaigns_insights;
delete from campaigns;
delete from adsets_insights_actions;
delete from adsets_insights;
delete from adsets;
delete from ad_creatives;
delete from ad_insights_actions;
delete from ad_insights;
delete from ads;

ALTER TABLE `metrics`.`account_insights_actions` 
ADD COLUMN `action_values` VARCHAR(45) NULL;
ALTER TABLE `metrics`.`adsets_insights_actions` 
ADD COLUMN `action_values` VARCHAR(45) NULL;
ALTER TABLE `metrics`.`ad_insights_actions` 
ADD COLUMN `action_values` VARCHAR(45) NULL;
ALTER TABLE `metrics`.`campaigns_insights_actions` 
ADD COLUMN `action_values` VARCHAR(45) NULL;

select * from accounts;
