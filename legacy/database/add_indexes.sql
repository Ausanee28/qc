-- QC Lab Tracking System
-- Index Optimization Script 
-- Purpose: Add indexes to improve query performance for Dashboard and Reports.

-- Add indexes to Transaction_Header
CREATE INDEX idx_status ON Transaction_Header (status);
CREATE INDEX idx_receive_date ON Transaction_Header (receive_date);
CREATE INDEX idx_dmc ON Transaction_Header (dmc);

-- Add indexes to Transaction_Detail
CREATE INDEX idx_transaction_id ON Transaction_Detail (transaction_id);
CREATE INDEX idx_judgement ON Transaction_Detail (judgement);
CREATE INDEX idx_start_time ON Transaction_Detail (start_time);
