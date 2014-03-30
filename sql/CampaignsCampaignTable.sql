-- Table for information about campaigns
CREATE TABLE IF NOT EXISTS /*_*/campaigns_campaign (

	-- Auto-increment id
	campaign_id int unsigned NOT NULL PRIMARY KEY auto_increment,

	-- Time the campaign was created
	campaign_time_created varbinary(14) NOT NULL,

	-- Time the campaign ended
	campaign_time_ended varbinary(14),

	-- A string to identify the campaign in URLs
	campaign_url_key varchar(255) NOT NULL UNIQUE,

	-- Name of the campaign
	campaign_name varchar(255) NOT NULL UNIQUE,

	-- String title of the campaign's home page on this wiki
	campaign_home_page_title_text varchar(255)

) /*$wgDBTableOptions*/;

-- Indexes
CREATE UNIQUE INDEX /*i*/campaigns_campaign_id ON
	/*_*/campaigns_campaign (campaign_id);

CREATE UNIQUE INDEX /*i*/campaigns_campaign_url_id ON
	/*_*/campaigns_campaign (campaign_url_key);

CREATE UNIQUE INDEX /*i*/campaigns_campaign_name ON
	/*_*/campaigns_campaign (campaign_name);
