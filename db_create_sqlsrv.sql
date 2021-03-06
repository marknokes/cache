CREATE DATABASE [php_ucache]
GO
USE [php_ucache]
GO
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
CREATE TABLE [dbo].[cache_data](
	[id] [varchar](50) PRIMARY KEY NOT NULL,
	[last_run] [bigint] NOT NULL,
	[cache_content] [nvarchar](max) NOT NULL,
	[num_hits] [int] DEFAULT 0 NOT NULL
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]

GO
SET ANSI_PADDING OFF
GO
