const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
const server = http.createServer(app);

app.use(cors({
  origin: '*',
  methods: ['GET', 'POST']
}));

const io = new Server(server, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST']
  }
});

// Store online users and rooms
const onlineUsers = new Map();
const rooms = new Map(); // roomId -> [{userId, socketId}]

io.on('connection', (socket) => {
  console.log('User connected:', socket.id);

  // Join user
  socket.on('join', (data) => {
    const { userId, token } = data;
    onlineUsers.set(userId, socket.id);
    socket.userId = userId;
    socket.join(`user_${userId}`);
    io.emit('onlineUsers', Array.from(onlineUsers.keys()));
  });

  // Message
  socket.on('privateMessage', (data) => {
    const { toUserId, message } = data;
    const toSocket = onlineUsers.get(toUserId);
    if (toSocket) {
      io.to(toSocket).emit('privateMessage', {
        from: socket.userId,
        message,
        timestamp: new Date().toISOString()
      });
    }
  });

  // Create/join room (team chat)
  socket.on('joinRoom', (roomId) => {
    socket.join(roomId);
    rooms.set(roomId, (rooms.get(roomId) || []).concat({userId: socket.userId, socketId: socket.id}));
    socket.to(roomId).emit('userJoinedRoom', socket.userId);
  });

  socket.on('roomMessage', (data) => {
    const { roomId, message } = data;
    socket.to(roomId).emit('roomMessage', {
      from: socket.userId,
      message,
      timestamp: new Date().toISOString()
    });
  });

  // Typing
  socket.on('typing', (data) => {
    const { toUserId, isTyping } = data;
    const toSocket = onlineUsers.get(toUserId);
    if (toSocket) {
      io.to(toSocket).emit('typing', { from: socket.userId, isTyping });
    }
  });

  socket.on('disconnect', () => {
    if (socket.userId) {
      onlineUsers.delete(socket.userId);
      io.emit('onlineUsers', Array.from(onlineUsers.keys()));
    }
    console.log('User disconnected:', socket.id);
  });
});

server.listen(3001, () => {
  console.log('🚀 Chat server running on http://localhost:3001');
});
