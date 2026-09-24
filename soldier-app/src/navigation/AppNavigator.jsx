import React, { useEffect, useState } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import LoginScreen from '../screens/LoginScreen';
import DashboardScreen from '../screens/DashboardScreen';
import TicketsScreen from '../screens/TicketsScreen';
import CreateTicketScreen from '../screens/CreateTicketScreen';
import TicketDetailScreen from '../screens/TicketDetailScreen';
import { ActivityIndicator, View, Text, Image } from 'react-native';
import client from '../api/client';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

function HeaderTitle({ title }) {
  const [branding, setBranding] = useState(null);
  useEffect(() => {
    client.get('/branding').then(({data})=> setBranding(data)).catch(()=>{});
  }, []);
  const name = branding?.app_name || 'NA E-Ticket';
  const motto = branding?.app_motto || 'Nigerian Army';
  const logoUri = branding?.app_logo;
  return (
    <View style={{ flexDirection:'row', alignItems:'center', gap:9 }}>
      {logoUri ? (
        <Image source={{ uri: logoUri }} style={{ width:32, height:32, borderRadius:6, backgroundColor:'#fff' }} resizeMode="contain" />
      ) : (
        <Image source={require('../../assets/icon.png')} style={{ width:32, height:32, borderRadius:6, backgroundColor:'#fff' }} resizeMode="contain" />
      )}
      <View>
        <Text style={{ color:'#fff', fontWeight:'800', fontSize:14, lineHeight:15 }}>{name}</Text>
        <Text style={{ color:'#cbd5e1', fontSize:9, lineHeight:10 }}>{title || motto}</Text>
      </View>
    </View>
  );
}

function Tabs() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown:true,
        headerStyle:{ backgroundColor:'#0b3d0b' },
        headerTintColor:'#fff',
        headerTitle: () => <HeaderTitle title={route.name==='Dashboard'?'Dashboard':'My Tickets'} />,
        tabBarActiveTintColor:'#0b3d0b',
        tabBarInactiveTintColor:'#64748b',
        tabBarStyle:{ backgroundColor:'#fff', paddingBottom:4, height:58 },
        tabBarIcon: ({ focused, color, size }) => {
          const name = route.name==='Dashboard' ? (focused?'grid':'grid-outline') : (focused?'mail':'mail-outline');
          return <Ionicons name={name} size={22} color={color} />;
        },
      })}
    >
      <Tab.Screen name="Dashboard" component={DashboardScreen} options={{ tabBarLabel:'Dashboard' }} />
      <Tab.Screen name="Tickets" component={TicketsScreen} options={{ tabBarLabel:'My Tickets' }} />
    </Tab.Navigator>
  );
}

export default function AppNavigator() {
  const { user, loading } = useAuth();
  if (loading) return <View style={{flex:1, justifyContent:'center', alignItems:'center'}}><ActivityIndicator size="large" color="#0b3d0b" /></View>;

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerStyle:{ backgroundColor:'#0b3d0b' }, headerTintColor:'#fff', headerTitleStyle:{ fontWeight:'800' } }}>
        {!user ? (
          <Stack.Screen name="Login" component={LoginScreen} options={{ headerShown:false }} />
        ) : (
          <>
            <Stack.Screen name="Main" component={Tabs} options={{ headerShown:false }} />
            <Stack.Screen name="CreateTicket" component={CreateTicketScreen} options={{ headerTitle: () => <HeaderTitle title="New Ticket" /> }} />
            <Stack.Screen name="TicketDetail" component={TicketDetailScreen} options={{ headerTitle: () => <HeaderTitle title="Ticket Detail" /> }} />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
