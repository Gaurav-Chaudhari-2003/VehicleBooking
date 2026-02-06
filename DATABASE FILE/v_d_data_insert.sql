INSERT INTO users (first_name, last_name, email, phone, role, password)
VALUES
    ('Amit', 'Waghmare', 'amitvwaghmare5@gmail.com', '9823852276', 'DRIVER', '$2y$dummy'),
    ('Ankit', 'Karade', 'ankitkarade01@gmail.com', '8999579955', 'DRIVER', '$2y$dummy'),
    ('Rahul', 'Meshram', 'rahul.meshram507@gmail.com', '8055204433', 'DRIVER', '$2y$dummy'),
    ('Surajkumar', 'Lanjewar', 'surajlanjewar1919@gmail.com', '7887357147', 'DRIVER', '$2y$dummy'),
    ('Achal', 'Patil', 'aslchalpatil@gmail.com', '9322873924', 'DRIVER', '$2y$dummy'),
    ('Jayant', 'Ikhar', 'jayantlkhar1967@gmail.com', '9370438782', 'DRIVER', '$2y$dummy'),
    ('Bhushan', 'Dhote', 'bhushandhote222@gmail.com', '9356996973', 'DRIVER', '$2y$dummy'),
    ('Amol', 'Nanandagawali', 'amolnandagawali83@gmail.com', '9604695300', 'DRIVER', '$2y$dummy'),
    ('Vikas', 'Dupare', 'vikasdupare69@gmail.com', '9370039191', 'DRIVER', '$2y$dummy'),
    ('Sushil', 'Thakur', 'sushilsingh71192@gmail.com', '8087275792', 'DRIVER', '$2y$dummy');


INSERT INTO vehicles (name, reg_no, category, fuel_type, capacity, ownership_type, status, image)
VALUES
    ('Mahindra Scorpio N', 'MH14MH3417', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '1y6QCKyGQ8JocaF_9X1oh5iPm5R7rKvBQ'),
    ('Bolero N', 'MH14LL9030', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '1taDEivo7g8s024rKG7S3hejtCJcIs_Oe'),
    ('Scorpio N', 'MH14LL6606', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '1my__qycguE8dCvwl8EKDmNHJrnfelgYq'),
    ('Scorpio N', 'MH14MH3163', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '192iPmzr8eFgvTSIpX3IQLcw6j4BnsYj'),
    ('Scorpio', 'MH14MH3156', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '1yzUy5NycjyfvK8yOuC4sewX_OETtQdZ9'),
    ('Scorpio N', 'MH14MH3154', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '15uwqNBtAs7E2XHBnI3_G4yYG7q_nzTEB'),
    ('Mahindra', 'MH14LL6033', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '1BhvOmkL8H1PSRI1_Iv853HluvaF5HK-J'),
    ('Scorpio', 'MH14MH3159', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '13Kt_aSj5X6DFwD_AiCDA9fpkMLNQObmY'),
    ('Scorpio', 'MH14MH3418', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '1t0Jc03TxLYZo76CDLk7nmamM-pEvJA4V'),
    ('Scorpio N', 'MH14MH3162', 'SEDAN', 'DIESEL', 7, 'DEPARTMENT', 'AVAILABLE', '1u_vGxhxThRYLSamTLMDx3ap4EYBI6YRW');

SET FOREIGN_KEY_CHECKS = 0;
INSERT INTO drivers (user_id, license_no, license_expiry, experience_years, status)
VALUES
    (3, 'MH3120080000937', '2030-07-09', 25, 'ACTIVE'),
    (4, 'MH3120090068692', '2030-01-28', 16, 'ACTIVE'),
    (5, 'MH4920220017319', '2030-11-18', 10, 'ACTIVE'),
    (6, 'MH3120110038286', '2030-11-17', 18, 'ACTIVE'),
    (7, 'MH4020170025670', '2030-10-06', 9, 'ACTIVE'),
    (8, 'MH3120100014481', '2027-01-10', 35, 'ACTIVE'),
    (9, 'MH4920220028730', '2027-10-27', 6, 'ACTIVE'),
    (10, 'MH3120080051877', '2027-05-16', 14, 'ACTIVE'),
    (11, 'MH3120080075924', '2030-08-10', 25, 'ACTIVE'),
    (12,'MH3120090124555', '2026-11-10', 30, 'ACTIVE');

