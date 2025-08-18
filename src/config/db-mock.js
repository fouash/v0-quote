// src/config/db-mock.js - Mock database for testing
const mockData = {
    rfqs: [],
    users: [],
    otps: [],
    bids: []
};

let idCounter = 1;

const mockDb = {
    query: async (text, params = []) => {
        console.log('Mock DB Query:', text, params);
        
        // Mock OTP insertion
        if (text.includes('INSERT INTO otps')) {
            return { rows: [{ id: idCounter++ }] };
        }
        
        // Mock OTP verification
        if (text.includes('SELECT id FROM otps')) {
            return { rows: [{ id: 1 }] };
        }
        
        // Mock user operations
        if (text.includes('SELECT id, role, name, email, slug FROM users')) {
            return { rows: [] };
        }
        
        if (text.includes('INSERT INTO users')) {
            const user = { id: idCounter++, role: params[0], name: params[1], email: params[2], slug: params[3] };
            mockData.users.push(user);
            return { rows: [user] };
        }
        
        // Mock RFQ operations
        if (text.includes('SELECT * FROM rfqs')) {
            const sampleRfqs = [
                { id: 1, title: 'Sample RFQ', description: 'Test description', status: 'open', created_at: new Date() },
                { id: 2, title: 'Another RFQ', description: 'Another test', status: 'open', created_at: new Date() }
            ];
            return { rows: sampleRfqs };
        }
        
        if (text.includes('INSERT INTO rfqs')) {
            const rfq = { 
                id: idCounter++, 
                title: params[0], 
                description: params[1], 
                buyer_id: params[2],
                created_at: new Date()
            };
            mockData.rfqs.push(rfq);
            return { rows: [rfq] };
        }
        
        // Default empty response
        return { rows: [] };
    },
    
    connect: async () => ({
        query: mockDb.query,
        release: () => {},
    })
};

module.exports = mockDb;