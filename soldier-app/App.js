import 'react-native-gesture-handler';
import { StatusBar } from 'expo-status-bar';
import { AuthProvider } from './src/context/AuthContext';
import { LoadingProvider } from './src/context/LoadingContext';
import AppNavigator from './src/navigation/AppNavigator';

export default function App() {
  return (
    <LoadingProvider>
      <AuthProvider>
        <StatusBar style="light" backgroundColor="#0b3d0b" />
        <AppNavigator />
      </AuthProvider>
    </LoadingProvider>
  );
}
