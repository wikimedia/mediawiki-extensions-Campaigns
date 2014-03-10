-- Table for information about campaigns
CREATE TABLE IF NOT EXISTS /*_*/campaigns_campaign (

	-- Auto-increment id
	campaign_id int unsigned NOT NULL PRIMARY KEY auto_increment,

	-- Time the campaign was created
	campaign_time_created varbinary(14) NOT NULL,

	-- A string to identify the campaign in URLs
	campaign_url_id varchar(255) NOT NULL UNIQUE,

	-- Name of the campaign
	campaign_name varchar(255) NOT NULL UNIQUE,

	-- ID of a page about the campaign, foreign key on page.page_id
	campaign_wikipage_id int unsigned,

	-- Flag to use only event logging to record participations in this campaign
	-- (i.e., just log via server-side when an account is created with the
	-- campaign's URL ID in the URL)
	-- Note that MySQL has no native boolean type, it's just a synonym for this
	campaign_only_event_logging tinyint(1) NOT NULL default false

) /*$wgDBTableOptions*/;

-- Indexes
CREATE UNIQUE INDEX /*i*/campaigns_campaign_id ON
	/*_*/campaigns_campaign (campaign_id);

CREATE UNIQUE INDEX /*i*/campaigns_campaign_url_id ON
	/*_*/campaigns_campaign (campaign_url_id);

CREATE UNIQUE INDEX /*i*/campaigns_campaign_name ON
	/*_*/campaigns_campaign (campaign_name);
